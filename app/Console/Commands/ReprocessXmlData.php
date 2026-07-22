<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class ReprocessXmlData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimbursement:reprocess-xml {--limit= : Limit the number of records to process} {--id= : Process a specific record ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vuelve a leer los archivos XML de los reembolsos existentes para extraer nuevos campos de metadatos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando barrido de archivos XML...');

        $query = Reimbursement::whereNotNull('xml_path');

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        if ($this->option('limit')) {
            $query->limit($this->option('limit'));
        }

        $reimbursements = $query->get();
        $total = $reimbursements->count();

        if ($total === 0) {
            $this->warn('No se encontraron reembolsos con archivos XML para procesar.');
            return;
        }

        $this->info("Se procesarán {$total} registros.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $errors = 0;

        foreach ($reimbursements as $r) {
            try {
                if (!Storage::exists($r->xml_path)) {
                    Log::warning("Archivo XML no encontrado en storage: {$r->xml_path} (ID: {$r->id})");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $xmlContent = Storage::get($r->xml_path);
                $data = $this->extractXmlData($xmlContent);

                if (!$data || empty($data['uuid'])) {
                    Log::error("No se pudo extraer el UUID del XML: {$r->xml_path} (ID: {$r->id})");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Actualizar los campos del modelo con la información del XML
                $r->update([
                    'uuid' => $data['uuid'],
                    'rfc_emisor' => $data['rfc_emisor'],
                    'nombre_emisor' => $data['nombre_emisor'],
                    'rfc_receptor' => $data['rfc_receptor'],
                    'nombre_receptor' => $data['nombre_receptor'],
                    'folio_interno_proveedor' => $data['folio_interno_proveedor'] ?? null,
                    'fecha' => $data['fecha'],
                    'total' => $data['total'],
                    'subtotal' => $data['subtotal'],
                    'impuestos' => $data['impuestos'],
                    'moneda' => $data['moneda'],
                    'tipo_comprobante' => $data['tipo_comprobante'],
                    'metodo_pago' => $data['metodo_pago'],
                    'forma_pago' => $data['forma_pago'],
                    'uso_cfdi' => $data['uso_cfdi'],
                    'lugar_expedicion' => $data['lugar_expedicion'],
                    'regimen_fiscal_emisor' => $data['regimen_fiscal_emisor'],
                    'retencion_iva' => $data['retencion_iva'] ?? 0,
                    'monto_iva' => $data['monto_iva'] ?? 0,
                    'monto_isr' => $data['monto_isr'] ?? 0,
                    'cfdi_conceptos' => $data['cfdi_conceptos'] ?? [],
                    'impuestos_locales' => $data['impuestos_locales'] ?? [],
                ]);

                // NUEVO: Validar contra el PDF si existe para marcar si UUID y Monto están "OK"
                if ($r->pdf_path && Storage::exists($r->pdf_path)) {
                    $this->validatePdf($r, $data['uuid'], $data['total']);
                }

                $success++;
            } catch (\Exception $e) {
                Log::error("Error procesando XML para ID {$r->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nProceso finalizado.");
        $this->info("Exitosos: {$success}");
        $this->info("Errores: {$errors}");
    }

    /**
     * Valida el PDF contra los datos del XML.
     */
    private function validatePdf($reimbursement, $uuid, $total)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile(Storage::path($reimbursement->pdf_path));
            $text = $pdf->getText();
            $cleanText = str_replace([' ', "\n", "\r", "\t"], '', $text);

            $uuidFound = stripos($text, (string)$uuid) !== false || stripos($cleanText, (string)$uuid) !== false;
            
            $totalFormatted = number_format((float)$total, 2);
            $totalRaw = (string)$total;
            $totalFound = str_contains($text, $totalFormatted) || str_contains($text, $totalRaw) || 
                         str_contains($cleanText, $totalFormatted) || str_contains($cleanText, $totalRaw);

            $reimbursement->update([
                'validation_data' => [
                    'uuid_match' => $uuidFound,
                    'total_match' => $totalFound,
                    'xml_matched' => true,
                    'pdf_matched' => true,
                    'message' => $uuidFound ? 'Validación Automática: UUID encontrado.' : 'Validación Automática: UUID NO encontrado.',
                    'last_validated_at' => now()->toDateTimeString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error validando PDF para ID {$reimbursement->id}: " . $e->getMessage());
        }
    }

    /**
     * Extrae los metadatos del XML del CFDI.
     * Copiado de ReimbursementController para mantener consistencia.
     */
    private function extractXmlData($xmlContent)
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            if (!$xml) return null;

            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/3');
            $xml->registerXPathNamespace('tfd', $ns['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital');

            // Parse XML Data
            $total = (string)$xml['Total'];
            $subtotal = (string)$xml['SubTotal'];
            $moneda = (string)$xml['Moneda'];
            $folioInternoProveedor = (string)$xml['Folio'] ?? null;
            $fechaAttr = (string)$xml['Fecha'];
            $fecha = $fechaAttr ? date('Y-m-d', strtotime($fechaAttr)) : null;
            $tipo = (string)$xml['TipoDeComprobante'];
            $metodoPago = (string)$xml['MetodoPago'];
            $formaPago = (string)$xml['FormaPago'];
            $lugarExpedicion = (string)$xml['LugarExpedicion'];

            // Extraction of Taxes (Impuestos)
            $impuestos = 0;
            $impuestosNode = $xml->xpath('//cfdi:Comprobante/cfdi:Impuestos | //cfdi:Impuestos');
            if ($impuestosNode) {
                foreach ($impuestosNode as $node) {
                    if (isset($node['TotalImpuestosTrasladados'])) {
                        $impuestos = (float)$node['TotalImpuestosTrasladados'];
                        break;
                    }
                }
            }

            $montoIva = $this->sumTaxAmounts(
                $xml,
                [
                    '//cfdi:Comprobante/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado',
                    '//cfdi:Concepto/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado',
                ],
                '002'
            );
            $retencionIva = $this->sumTaxAmounts(
                $xml,
                [
                    '//cfdi:Comprobante/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion',
                    '//cfdi:Concepto/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion',
                ],
                '002'
            );
            $montoIsr = $this->sumTaxAmounts(
                $xml,
                [
                    '//cfdi:Comprobante/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion',
                    '//cfdi:Concepto/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion',
                ],
                '001'
            );

            $cfdiNamespace = $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
            $conceptos = collect($xml->xpath('//cfdi:Conceptos/cfdi:Concepto') ?: [])
                ->map(function (\SimpleXMLElement $concepto) use ($cfdiNamespace) {
                    $concepto->registerXPathNamespace('cfdi', $cfdiNamespace);
                    $ivaTraslados = collect($concepto->xpath('./cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado') ?: [])
                        ->filter(fn (\SimpleXMLElement $traslado) => (string) ($traslado['Impuesto'] ?? '') === '002')
                        ->map(fn (\SimpleXMLElement $traslado) => [
                            'base' => isset($traslado['Base']) ? round((float) $traslado['Base'], 2) : 'NH',
                            'tasa_o_cuota' => isset($traslado['TasaOCuota']) && (string) $traslado['TasaOCuota'] !== '' ? (string) $traslado['TasaOCuota'] : 'NH',
                            'importe' => isset($traslado['Importe']) ? round((float) $traslado['Importe'], 2) : 'NH',
                        ])
                        ->values()
                        ->all();

                    return [
                        'clave_prod_serv' => (string) ($concepto['ClaveProdServ'] ?? ''),
                        'descripcion' => (string) ($concepto['Descripcion'] ?? ''),
                        'importe' => round((float) ($concepto['Importe'] ?? 0), 2),
                        'iva_traslados' => $ivaTraslados,
                    ];
                })
                ->values()
                ->all();

            $impuestosLocales = collect($xml->xpath('//*[local-name()="TrasladosLocales"]') ?: [])
                ->map(fn (\SimpleXMLElement $traslado) => [
                    'imp_loc_trasladado' => (string) ($traslado['ImpLocTrasladado'] ?? ''),
                    'importe' => round((float) ($traslado['Importe'] ?? 0), 2),
                ])
                ->values()
                ->all();

            // Emisor / Receptor
            $emisorArr = $xml->xpath('//cfdi:Emisor');
            $emisor = $emisorArr ? $emisorArr[0] : null;
            
            $receptorArr = $xml->xpath('//cfdi:Receptor');
            $receptor = $receptorArr ? $receptorArr[0] : null;
            
            // Timbre Fiscal Digital
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            $uuid = $tfd ? (string)$tfd[0]['UUID'] : null;

            // Extraction of Additional Metadata (UsoCFDI, RegimenFiscal)
            $usoCfdi = $receptor ? (string)$receptor['UsoCFDI'] : null;
            $regimenFiscalEmisor = $emisor ? (string)$emisor['RegimenFiscal'] : null;

            return [
                'uuid' => $uuid,
                'rfc_emisor' => $emisor ? (string)$emisor['Rfc'] : 'N/A',
                'nombre_emisor' => $emisor ? (string)$emisor['Nombre'] : 'N/A',
                'rfc_receptor' => $receptor ? (string)$receptor['Rfc'] : 'N/A',
                'nombre_receptor' => $receptor ? (string)$receptor['Nombre'] : 'N/A',
                'folio_interno_proveedor' => $folioInternoProveedor,
                'fecha' => $fecha,
                'total' => (float)$total,
                'subtotal' => (float)$subtotal,
                'impuestos' => (float)$impuestos,
                'moneda' => $moneda,
                'tipo_comprobante' => $tipo,
                'metodo_pago' => $metodoPago,
                'forma_pago' => $formaPago,
                'uso_cfdi' => $usoCfdi,
                'lugar_expedicion' => $lugarExpedicion,
                'regimen_fiscal_emisor' => $regimenFiscalEmisor,
                'retencion_iva' => $retencionIva,
                'monto_iva' => $montoIva,
                'monto_isr' => $montoIsr,
                'cfdi_conceptos' => $conceptos,
                'impuestos_locales' => $impuestosLocales,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function sumTaxAmounts(\SimpleXMLElement $xml, array $paths, string $taxCode): float
    {
        foreach ($paths as $path) {
            $nodes = $xml->xpath($path);
            if (!$nodes) {
                continue;
            }

            $amount = 0.0;
            foreach ($nodes as $node) {
                if ((string) ($node['Impuesto'] ?? '') !== $taxCode) {
                    continue;
                }

                $amount += (float) ($node['Importe'] ?? 0);
            }

            if ($amount > 0) {
                return round($amount, 2);
            }
        }

        return 0.0;
    }
}
