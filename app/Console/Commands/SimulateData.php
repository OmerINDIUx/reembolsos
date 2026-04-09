<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\CostCenter;
use App\Models\TravelEvent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SimulateData extends Command
{
    protected $signature = 'reimbursement:simulate';
    protected $description = 'Register simulation data from public/storage/xml-simulaciones';

    public function handle()
    {
        $this->info('Iniciando simulación de datos...');

        $users = User::all();
        $costCenters = CostCenter::all();
        
        if ($users->isEmpty() || $costCenters->isEmpty()) {
            $this->error('Se necesitan al menos un usuario y un centro de costos en la base de datos.');
            return;
        }

        $xmlPath = public_path('storage/xml-simulaciones/xmls');
        $pdfPath = public_path('storage/xml-simulaciones/pdfs');
        $tripPath = public_path('storage/xml-simulaciones/trips/vencia-3');

        if (!is_dir($xmlPath)) {
            $this->error("No se encontró el directorio: $xmlPath");
            return;
        }

        $xmlFiles = glob("$xmlPath/*.xml");
        $this->info('Encontrados ' . count($xmlFiles) . ' archivos XML.');

        $categories = ['viaticos', 'comida', 'gasolina', 'mantenimiento', 'hospedaje', 'transporte', 'servicios', 'otros'];
        $statuses = ['pendiente', 'aprobado', 'pagado', 'rechazado'];

        // Create a Travel Event for the trips images
        $tripEvent = TravelEvent::updateOrCreate(
            ['name' => 'Simulación Vencia-3'],
            [
                'cost_center_id' => $costCenters->random()->id,
                'user_id' => $users->random()->id,
                'director_id' => $users->where('role', 'director')->first()->id ?? $users->random()->id,
                'code' => 'VENC-003',
                'status' => 'active',
                'start_date' => now()->subMonths(1),
                'end_date' => now(),
                'location' => 'Cancún, México',
                'trip_type' => 'nacional',
                'description' => 'Evento de simulación para pruebas de gasto de viaje.'
            ]
        );

        $bar = $this->output->createProgressBar(count($xmlFiles));
        $bar->start();

        foreach ($xmlFiles as $file) {
            try {
                $xmlContent = file_get_contents($file);
                $data = $this->extractXmlData($xmlContent);

                if (empty($data['uuid'])) {
                    continue;
                }

                // Check for duplicates
                if (Reimbursement::where('uuid', $data['uuid'])->exists()) {
                    $bar->advance();
                    continue;
                }

                $user = $users->random();
                $cc = $costCenters->random();
                
                // Random date between Jan and April 2026
                $randomDate = Carbon::create(2026, rand(1, 4), rand(1, 28));
                $week = $randomDate->addDays(2)->format('W-Y');

                $status = collect($statuses)->random();
                
                // Copy XML to storage
                $uuid = $data['uuid'];
                $storagePathXml = "xmls/{$uuid}.xml";
                Storage::put($storagePathXml, $xmlContent);

                // Copy PDF if exists
                $baseName = pathinfo($file, PATHINFO_FILENAME);
                $matchingPdf = "$pdfPath/$baseName.pdf";
                $storagePathPdf = null;
                if (file_exists($matchingPdf)) {
                    $storagePathPdf = "pdfs/{$uuid}.pdf";
                    Storage::put($storagePathPdf, file_get_contents($matchingPdf));
                }

                // Simulate approvals for "pagado" or "aprobado"
                $approvalData = [];
                if ($status === 'pagado' || $status === 'aprobado') {
                    $admin = User::where('role', 'admin')->first() ?? $users->first();
                    $approvalData = [
                        'approved_by_director_id' => $admin->id,
                        'approved_by_director_at' => $randomDate,
                        'approved_by_control_id' => $admin->id,
                        'approved_by_control_at' => $randomDate,
                        'approved_by_executive_id' => $admin->id,
                        'approved_by_executive_at' => $randomDate,
                    ];
                    if ($status === 'pagado') {
                        $approvalData['approved_by_cxp_id'] = $admin->id;
                        $approvalData['approved_by_cxp_at'] = $randomDate;
                        $approvalData['approved_by_treasury_id'] = $admin->id;
                        $approvalData['approved_by_treasury_at'] = $randomDate;
                    }
                }

                $reimbursement = Reimbursement::create(array_merge([
                    'type' => collect(['reembolso', 'fondo_fijo', 'comida'])->random(),
                    'cost_center_id' => $cc->id,
                    'user_id' => $user->id,
                    'payee_id' => $user->id,
                    'week' => $week,
                    'category' => collect($categories)->random(),
                    'uuid' => $uuid,
                    'rfc_emisor' => $data['rfc_emisor'],
                    'nombre_emisor' => $data['nombre_emisor'],
                    'rfc_receptor' => $data['rfc_receptor'],
                    'nombre_receptor' => $data['nombre_receptor'],
                    'folio' => $data['folio'],
                    'fecha' => $data['fecha'],
                    'total' => $data['total'],
                    'subtotal' => $data['subtotal'],
                    'impuestos' => $data['impuestos'],
                    'moneda' => $data['moneda'],
                    'xml_path' => $storagePathXml,
                    'pdf_path' => $storagePathPdf,
                    'status' => $status,
                    'observaciones' => 'Simulación automática.',
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ], $approvalData));

            } catch (\Exception $e) {
                $this->error("\nError procesando {pathinfo($file, PATHINFO_BASENAME)}: " . $e->getMessage());
            }

            $bar->advance();
        }

        // Process Trip images
        if (is_dir($tripPath)) {
            $images = glob("$tripPath/*.{jpg,png,jpeg}", GLOB_BRACE);
            foreach ($images as $img) {
                $uuid = Str::uuid()->toString();
                $storagePathTicket = "tickets/{$uuid}." . pathinfo($img, PATHINFO_EXTENSION);
                Storage::put($storagePathTicket, file_get_contents($img));

                Reimbursement::create([
                    'type' => 'viaje',
                    'travel_event_id' => $tripEvent->id,
                    'cost_center_id' => $tripEvent->cost_center_id,
                    'user_id' => $tripEvent->user_id,
                    'payee_id' => $tripEvent->user_id,
                    'week' => $tripEvent->start_date->format('W-Y'),
                    'category' => 'viaticos',
                    'folio' => 'SIM-TRIP-' . Str::upper(Str::random(5)),
                    'fecha' => $tripEvent->start_date,
                    'total' => rand(500, 3000),
                    'subtotal' => rand(400, 2500),
                    'impuestos' => rand(50, 500),
                    'moneda' => 'MXN',
                    'ticket_path' => $storagePathTicket,
                    'status' => 'en_evento',
                    'observaciones' => 'Simulación de gasto de viaje.',
                    'created_at' => $tripEvent->start_date,
                ]);
            }
        }

        $bar->finish();
        $this->info("\nSimulación completada.");
    }

    private function extractXmlData($xmlContent)
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            if (!$xml) return ['uuid' => null];
            
            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/3');
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

            $total = (string)$xml['Total'];
            $subtotal = (string)$xml['SubTotal'];
            $moneda = (string)$xml['Moneda'] ?? 'MXN';
            $folio = (string)$xml['Folio'] ?? 'N/A';
            $fecha = (string)$xml['Fecha'];
            
            $emisorArr = $xml->xpath('//cfdi:Emisor');
            $emisor = $emisorArr ? $emisorArr[0] : null;
            
            $receptorArr = $xml->xpath('//cfdi:Receptor');
            $receptor = $receptorArr ? $receptorArr[0] : null;
            
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            $uuid = $tfd ? (string)$tfd[0]['UUID'] : null;

            return [
                'uuid' => $uuid,
                'rfc_emisor' => $emisor ? (string)$emisor['Rfc'] : 'N/A',
                'nombre_emisor' => $emisor ? (string)$emisor['Nombre'] : 'N/A',
                'rfc_receptor' => $receptor ? (string)$receptor['Rfc'] : 'N/A',
                'nombre_receptor' => $receptor ? (string)$receptor['Nombre'] : 'N/A',
                'folio' => $folio,
                'fecha' => $fecha ? date('Y-m-d', strtotime($fecha)) : null,
                'total' => $total,
                'subtotal' => $subtotal,
                'impuestos' => (float)$total - (float)$subtotal,
                'moneda' => $moneda,
            ];
        } catch (\Exception $e) {
            return ['uuid' => null];
        }
    }
}
