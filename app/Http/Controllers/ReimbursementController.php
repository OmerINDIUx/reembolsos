<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class ReimbursementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reimbursements = Reimbursement::orderBy('created_at', 'desc')->paginate(10);
        return view('reimbursements.index', compact('reimbursements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $type = $request->query('type');
        $allowedTypes = ['reembolso', 'fondo_fijo', 'comida', 'viaje'];

        if (!$type || !in_array($type, $allowedTypes)) {
            return view('reimbursements.select_type');
        }

        return view('reimbursements.create', compact('type'));
    }

    /**
     * Parse XML and return data as JSON.
     */
    public function parseCfdi(Request $request)
    {
        $request->validate([
            'xml_file' => 'required|file|mimes:xml',
            'pdf_file' => 'nullable|file|mimes:pdf',
        ]);

        try {
            $xmlContent = file_get_contents($request->file('xml_file')->getRealPath());
            $data = $this->extractXmlData($xmlContent);

            if (empty($data['uuid'])) {
                return response()->json(['error' => 'No se encontró un UUID válido en el XML provided.'], 422);
            }

            if (Reimbursement::where('uuid', $data['uuid'])->exists()) {
                 return response()->json(['error' => 'Este CFDI (UUID: ' . $data['uuid'] . ') ya ha sido registrado previamente.'], 422);
            }

            // PDF Validation
            $pdfValidation = null;
            if ($request->hasFile('pdf_file')) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($request->file('pdf_file')->getRealPath());
                    $text = $pdf->getText();
                    
                    // Simple cleaning of text for easier matching
                    $cleanText = str_replace([' ', "\n", "\r", "\t"], '', $text); 
                    
                    // UUID Check
                    $uuidFound = str_contains($text, $data['uuid']); // UUIDs usually distinct enough
                    
                    // Total Check (naive)
                    // Try to match formatted or unformatted total
                    $total = $data['total'];
                    $totalFormatted = number_format((float)$total, 2); // 1,234.56
                    $totalRaw = $total; // 1234.56
                    
                    $totalFound = str_contains($text, $totalFormatted) || str_contains($text, $totalRaw);

                    $pdfValidation = [
                        'uuid_match' => $uuidFound,
                        'total_match' => $totalFound,
                        'message' => $uuidFound ? 'UUID encontrado en PDF.' : 'Advertencia: UUID NO encontrado en PDF.',
                    ];
                } catch (\Exception $e) {
                    $pdfValidation = ['error' => 'No se pudo leer el PDF: ' . $e->getMessage()];
                }
            }

            return response()->json(array_merge($data, ['pdf_validation' => $pdfValidation]));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar el XML: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Helper to extract data from XML content.
     */
    private function extractXmlData($xmlContent)
    {
        $xml = simplexml_load_string($xmlContent);
        $ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
        $xml->registerXPathNamespace('tfd', $ns['tfd'] ?? ($ns['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital')); // Fallback

        // Parse XML Data
        $total = (string)$xml['Total'];
        $subtotal = (string)$xml['SubTotal'];
        $moneda = (string)$xml['Moneda'];
        $folio = (string)$xml['Folio'];
        $fecha = (string)$xml['Fecha'];
        $tipo = (string)$xml['TipoDeComprobante'];

        // Emisor / Receptor
        $emisor = $xml->xpath('//cfdi:Emisor')[0];
        $receptor = $xml->xpath('//cfdi:Receptor')[0];
        
        // Timbre Fiscal Digital
        $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
        $uuid = $tfd ? (string)$tfd[0]['UUID'] : null;

        return [
            'uuid' => $uuid,
            'rfc_emisor' => (string)$emisor['Rfc'],
            'nombre_emisor' => (string)$emisor['Nombre'],
            'rfc_receptor' => (string)$receptor['Rfc'],
            'nombre_receptor' => (string)$receptor['Nombre'],
            'folio' => $folio,
            'fecha' => $fecha,
            'total' => $total,
            'subtotal' => $subtotal,
            'moneda' => $moneda,
            'tipo_comprobante' => $tipo,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:reembolso,fondo_fijo,comida,viaje',
            'xml_file' => 'required|file|mimes:xml',
            'pdf_file' => 'nullable|file|mimes:pdf',
            'uuid' => 'required|string|unique:reimbursements,uuid',
            'total' => 'required|numeric',
            // Add other validations as needed
        ]);

        $xmlPath = $request->file('xml_file')->store('xmls');
        $pdfPath = $request->file('pdf_file') ? $request->file('pdf_file')->store('pdfs') : null;

        // Use the submitted data, but we could also rely on the file if we wanted to enforce strictness.
        // For "filling fields", we trust the form submission which should populate from the XML.
        
        Reimbursement::create([
            'type' => $request->type,
            'uuid' => $request->uuid,
            'rfc_emisor' => $request->rfc_emisor,
            'nombre_emisor' => $request->nombre_emisor,
            'rfc_receptor' => $request->rfc_receptor,
            'nombre_receptor' => $request->nombre_receptor,
            'folio' => $request->folio,
            'fecha' => $request->fecha,
            'total' => $request->total,
            'subtotal' => $request->subtotal,
            'moneda' => $request->moneda,
            'tipo_comprobante' => $request->tipo_comprobante,
            'xml_path' => $xmlPath,
            'pdf_path' => $pdfPath,
            'status' => 'pendiente',
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('reimbursements.index')
                         ->with('success', 'Reembolso subido exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reimbursement $reimbursement)
    {
        return view('reimbursements.show', compact('reimbursement'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reimbursement $reimbursement)
    {
        $request->validate([
            'status' => 'required|in:pendiente,aprobado,rechazado',
        ]);

        $reimbursement->update(['status' => $request->status]);

        return back()->with('success', 'Estatus actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reimbursement $reimbursement)
    {
        if ($reimbursement->xml_path) Storage::delete($reimbursement->xml_path);
        if ($reimbursement->pdf_path) Storage::delete($reimbursement->pdf_path);
        
        $reimbursement->delete();

        return redirect()->route('reimbursements.index')
                         ->with('success', 'Reembolso eliminado.');
    }
}
