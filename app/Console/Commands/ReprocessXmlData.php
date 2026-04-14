<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
                    'folio' => $r->folio ?: $data['folio'], // No sobreescribir si ya tiene un folio asignado por el sistema
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
                ]);

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
            $folio = (string)$xml['Folio'] ?? 'N/A';
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
                'folio' => $folio,
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
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
