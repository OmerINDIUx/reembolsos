<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use App\Models\CostCenter;
use App\Models\User;
use App\Notifications\ReimbursementNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use SimpleXMLElement;
use Smalot\PdfParser\Parser;

use Illuminate\Support\Str;

class ReimbursementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Reimbursement::orderBy('created_at', 'desc');

        // Permissions Logic
        // Permissions Logic
        if ($user->isAdmin()) {
            // Admins see all
        } elseif ($user->isTreasury()) {
            // Tesorería ve solo lo aprobado por CXP o final (historial)
            $query->whereIn('status', ['aprobado_cxp', 'aprobado']);
        } elseif ($user->isCxp()) {
            // Cuentas por pagar (CXP) ven solo aprobado_director o lo que ellos aprobaron (aprobado_cxp) o final
            $query->whereIn('status', ['aprobado_director', 'aprobado_cxp', 'aprobado']);
        } elseif ($user->isDirector()) {
            // Directors see:
            // 1. Their own reimbursements
            // 2. Reimbursements from Cost Centers they direct
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter', function($q2) use ($user) {
                      $q2->where('director_id', $user->id);
                  });
            });
        } else {
            // Standard Users see only their own
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%")
                  ->orWhere('nombre_emisor', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $tab = $request->input('tab', 'active');
        
        $historyStatuses = ['aprobado', 'rechazado'];
        if ($user->isDirector() || $user->isCxp()) {
            $historyStatuses[] = 'aprobado_cxp';
        }
        if ($user->isDirector()) {
            $historyStatuses[] = 'aprobado_director'; 
        }

        if ($tab === 'history') {
            $query->whereIn('status', $historyStatuses);
        } else {
            $query->whereNotIn('status', $historyStatuses);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('fecha', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('fecha', '<=', $request->to_date);
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['folio', 'fecha', 'nombre_emisor', 'total', 'status', 'created_at'];
        
        // Remove the default orderBy to apply custom sorting
        $query->getQuery()->orders = null;

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $reimbursements = $query->paginate(10)->appends($request->all());
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

        $user = Auth::user();
        // Allow all users to select from all cost centers
        $costCenters = CostCenter::orderBy('name')->get();
        
        // Auto-fill week: WeekNumber-Year (e.g., 08-2026)
        $currentWeek = now()->format('W-Y');
        
        $categories = $this->getCategories();

        // Check for parent trip context
        $parentReimbursement = null;
        if ($request->has('trip_id')) {
            $parentReimbursement = Reimbursement::find($request->trip_id);
            // If parent exists, perhaps lock cost center/week?
            // For now, just pass it.
        }

        return view('reimbursements.create', compact('type', 'costCenters', 'currentWeek', 'categories', 'parentReimbursement'));
    }





    /**
     * Parse XML and return data as JSON.
     */
    public function parseCfdi(Request $request)
    {
        set_time_limit(120); // Increase time limit for slow PDF parsing

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

            $existingReimbursement = Reimbursement::where('uuid', $data['uuid'])->first();
            if ($existingReimbursement) {
                $statusMsg = '';
                if ($existingReimbursement->status === 'rechazado') {
                    $statusMsg = ' (Folio: ' . $existingReimbursement->folio . ') fue RECHAZADO definitivamente.';
                } elseif ($existingReimbursement->status === 'requiere_correccion') {
                    $statusMsg = ' (Folio: ' . $existingReimbursement->folio . ') REQUIERE CORRECCIÓN.';
                } else {
                    $statusMsg = ' (Folio: ' . $existingReimbursement->folio . ') se encuentra actualmente registrado con estatus: ' . strtoupper($existingReimbursement->status) . '.';
                }
                return response()->json(['error' => 'Atención: Este CFDI (UUID: ' . $data['uuid'] . ')' . $statusMsg], 422);
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
            'cost_center_id' => 'required|exists:cost_centers,id',
            'week' => 'required|string',
            'category' => ['nullable', Rule::requiredIf($request->type !== 'viaje'), Rule::in($this->getCategories())], // strict validation
            'xml_file' => 'nullable|file|mimes:xml', // handled manually below
            'uuid' => 'nullable|string', // handled manually
            'total' => 'nullable|numeric', // handled manually
            'attendees_count' => 'nullable|integer|required_if:type,comida',
            'attendees_names' => 'nullable|string',
            'location' => 'nullable|string|required_if:type,comida',
            'trip_nights' => 'nullable|integer|min:0|required_if:type,viaje',
            'trip_type' => 'nullable|in:nacional,internacional|required_if:type,viaje',
            'trip_destination' => 'nullable|string|required_if:type,viaje',
            'trip_start_date' => 'nullable|date|required_if:type,viaje',
            'trip_end_date' => 'nullable|date|after_or_equal:trip_start_date|required_if:type,viaje',
            'title' => 'nullable|string|required_if:type,viaje',
            'extra_files.*' => 'file|max:10240', // 10MB max
            'parent_id' => 'nullable|exists:reimbursements,id',
            'confirm_company' => Rule::requiredIf($request->type !== 'viaje'),
        ]);
        
        if ($request->type !== 'viaje') {
            $request->validate([
                'xml_file' => 'required',
                'uuid' => 'required',
                'total' => 'required',
            ]);
        }
        // But the previous prompts imply "Viaje" is a container.
        // Let's adjust validation for XML/PDF to be nullable if type is viaje
        


        $xmlPath = $request->file('xml_file') ? $request->file('xml_file')->store('xmls') : null;
        $pdfPath = $request->file('pdf_file') ? $request->file('pdf_file')->store('pdfs') : null;

        $reimbursement = Reimbursement::create([
            'type' => $request->type,
            'cost_center_id' => $request->cost_center_id,
            'week' => $request->week,
            'category' => $request->category ?? 'viaticos', // Default for trips if not set
            'uuid' => $request->uuid,
            'rfc_emisor' => $request->rfc_emisor,
            'nombre_emisor' => $request->nombre_emisor,
            'rfc_receptor' => $request->rfc_receptor,
            'nombre_receptor' => $request->nombre_receptor,
            'folio' => $request->folio, // Placeholder, updated below
            'fecha' => $request->fecha,
            'total' => $request->total,
            'subtotal' => $request->subtotal,
            'moneda' => $request->moneda,
            'tipo_comprobante' => $request->tipo_comprobante,
            'xml_path' => $xmlPath,
            'pdf_path' => $pdfPath,
            'status' => 'pendiente',
            'observaciones' => $request->observaciones,
            'attendees_count' => $request->attendees_count,
            'attendees_names' => $request->attendees_names,
            'location' => $request->location,
            'trip_nights' => $request->trip_nights,
            'trip_type' => $request->trip_type,
            'trip_destination' => $request->trip_destination,
            'trip_start_date' => $request->trip_start_date,
            'trip_end_date' => $request->trip_end_date,
            'title' => $request->title,
            'parent_id' => $request->parent_id,
            'company_confirmed' => $request->has('confirm_company') ? true : false,
            'validation_data' => $request->validation_data ? json_decode($request->validation_data, true) : null,
            'user_id' => Auth::id(),
        ]);

        // Generate Custom Folio: XXX-000000
        // Type Map: REE, FON, COM, VIA
        $prefix = strtoupper(substr($request->type, 0, 3));
        $reimbursement->folio = $prefix . '-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT);
        $reimbursement->save();

        // Notify Director
        if ($reimbursement->costCenter && $reimbursement->costCenter->director) {
            $reimbursement->costCenter->director->notify(new ReimbursementNotification(
                $reimbursement, 
                "Nueva solicitud ({$reimbursement->folio}) pendiente de tu aprobación.", 
                "info"
            ));
        }

        // Handle International Trip Files
        if ($request->type === 'viaje' && $request->trip_type === 'internacional' && $request->hasFile('extra_files')) {
            $folderName = Str::slug($request->title . '-' . $reimbursement->id);
            foreach($request->file('extra_files') as $file) {
                 $path = $file->store("trips/{$folderName}");
                 $reimbursement->files()->create([
                     'file_path' => $path,
                     'original_name' => $file->getClientOriginalName(),
                     'mime_type' => $file->getClientMimeType(),
                 ]);
            }
        }
        
        // Redirect logic
        if ($request->type === 'viaje') {
             return redirect()->route('reimbursements.show', $reimbursement)
                         ->with('success', 'Viaje creado exitosamente. Ahora puede agregar gastos.');
        }
        
        // If it was added to a parent trip
        if ($request->parent_id) {
             return redirect()->route('reimbursements.show', $request->parent_id)
                         ->with('success', 'Gasto agregado al viaje exitosamente.');
        }

        return redirect()->route('reimbursements.index')
                         ->with('success', 'Reembolso guardado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reimbursement $reimbursement)
    {
        $reimbursement->load(['files', 'children', 'parent', 'costCenter']);
        return view('reimbursements.show', compact('reimbursement'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reimbursement $reimbursement)
    {
        set_time_limit(120); // Increase time limit for slow PDF parsing

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Authorization Check
        $canApprove = $user->isAdmin() || 
                      $user->isTreasury() || 
                      $user->isCxp() || 
                      ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id);

        // Owner can update if it requires correction
        $isOwnerCorrecting = $user->id === $reimbursement->user_id && $reimbursement->status === 'requiere_correccion';

        if (!$canApprove && !$isOwnerCorrecting) {
            abort(403, 'No tienes permiso para modificar este reembolso.');
        }

        if ($isOwnerCorrecting && $request->has('is_resubmission')) {
            $request->validate([
                'pdf_file' => 'nullable|file|mimes:pdf',
                'user_correction_comment' => 'required|string',
                'attendees_count' => 'nullable|integer',
                'location' => 'nullable|string',
                'attendees_names' => 'nullable|string',
                'title' => 'nullable|string',
                'trip_destination' => 'nullable|string',
                'trip_nights' => 'nullable|integer',
                'trip_start_date' => 'nullable|date',
                'trip_end_date' => 'nullable|date',
            ]);
            
            $data = array_filter($request->only([
                'attendees_count', 'location', 'attendees_names',
                'title', 'trip_destination', 'trip_nights', 'trip_start_date', 'trip_end_date'
            ]), function($value) { return !is_null($value); });

            if ($request->hasFile('pdf_file')) {
                if ($reimbursement->pdf_path) Storage::delete($reimbursement->pdf_path);
                $data['pdf_path'] = $request->file('pdf_file')->store('pdfs');
                
                // Re-validate PDF against XML Data
                if (!empty($reimbursement->uuid)) {
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($request->file('pdf_file')->getRealPath());
                        $text = $pdf->getText();
                        $cleanText = str_replace([' ', "\n", "\r", "\t"], '', $text);
                        
                        // UUID Check
                        $uuidFound = stripos($text, (string)$reimbursement->uuid) !== false || stripos($cleanText, (string)$reimbursement->uuid) !== false;
                        
                        // Total Check
                        $total = $reimbursement->total;
                        $totalFormatted = number_format((float)$total, 2);
                        $totalRaw = $total;
                        $totalFound = str_contains($text, (string)$totalFormatted) || str_contains($text, (string)$totalRaw) || str_contains($cleanText, (string)$totalFormatted) || str_contains($cleanText, (string)$totalRaw);

                        $validationData = [
                            'uuid_match' => $uuidFound,
                            'total_match' => $totalFound,
                            'message' => $uuidFound ? 'UUID encontrado en PDF (Corrección).' : 'Advertencia: UUID NO encontrado en PDF (Corrección).',
                        ];
                        
                        $data['validation_data'] = $validationData;
                    } catch (\Exception $e) {
                        $validationData = is_array($reimbursement->validation_data) ? $reimbursement->validation_data : [];
                        $validationData['error'] = 'No se pudo leer el PDF corregido: ' . $e->getMessage();
                        $data['validation_data'] = $validationData;
                    }
                }
            }
            
            // Append correction note
            $currentObs = $reimbursement->observaciones;
            $newObs = "CORREGIDO por " . $user->name . " el " . now()->format('d/m/Y H:i') . ": " . $request->user_correction_comment;
            $data['observaciones'] = $currentObs ? ($currentObs . "\n" . $newObs) : $newObs;

            // Route back appropriately
            if ($reimbursement->approved_by_cxp_id !== null) {
                // Was rejected by Treasury
                $data['status'] = 'aprobado_cxp';
            } elseif ($reimbursement->approved_by_director_id !== null) {
                // Was rejected by CXP
                $data['status'] = 'aprobado_director';
            } else {
                // Was rejected by Director
                $data['status'] = 'pendiente';
            }
        } else {
            $request->validate([
                'status' => 'required|in:pendiente,aprobado,rechazado,requiere_correccion',
                'rejection_reason' => 'nullable|string|required_if:status,rechazado|required_if:status,requiere_correccion',
                'rejection_comment' => 'nullable|string',
            ]);

            $data = [];

            if ($request->status === 'rechazado' || $request->status === 'requiere_correccion') {
                 // Append rejection/correction reason
                 $currentObs = $reimbursement->observaciones;
                 $prefix = ($request->status === 'requiere_correccion') ? "REQUIERE CORRECCIÓN" : "RECHAZADO";
                 $newObs = $prefix . " por " . $user->name . ": " . $request->rejection_reason;
                 if ($request->rejection_comment) {
                     $newObs .= " - " . $request->rejection_comment;
                 }
                 $data['observaciones'] = $currentObs ? ($currentObs . "\n" . $newObs) : $newObs;
                 $data['status'] = $request->status;
                 
            } elseif ($request->status === 'aprobado') {
                // Logic: Director -> aprobado_director -> CXP -> aprobado_cxp -> Tesoreria -> aprobado (Final)
                if ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id) {
                    $data['status'] = 'aprobado_director';
                    $data['approved_by_director_id'] = $user->id;
                    $data['approved_by_director_at'] = now();
                }

                // CXP 
                if ($user->isCxp()) {
                    $data['status'] = 'aprobado_cxp';
                    $data['approved_by_cxp_id'] = $user->id;
                    $data['approved_by_cxp_at'] = now();
                }

                // Treasury or Admin can verify/finalize. 
                if ($user->isTreasury() || $user->isAdmin()) {
                    $data['status'] = 'aprobado';
                    $data['approved_by_treasury_id'] = $user->id;
                    $data['approved_by_treasury_at'] = now();
                }
            }
        }

        $reimbursement->update($data);

        // Notify based on status change
        if (isset($data['status'])) {
            $owner = $reimbursement->user;
            
            if ($data['status'] === 'aprobado_director') {
                if ($isOwnerCorrecting) {
                    $cxpUsers = User::where('role', 'accountant')->get();
                    foreach($cxpUsers as $cxp) {
                        $cxp->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio} corregido y reenviado a CXP.", "info"));
                    }
                } else {
                    // Notify CXP
                    $cxpUsers = User::where('role', 'accountant')->get();
                    foreach($cxpUsers as $cxp) {
                        $cxp->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio} aprobado por Director, pendiente de CXP.", "warning"));
                    }
                    // Notify Owner
                    if ($owner) {
                        $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} ha sido aprobado por el Director.", "info"));
                    }
                }
            } elseif ($data['status'] === 'aprobado_cxp') {
                if ($isOwnerCorrecting) {
                    // Resubmitted directly to Treasury
                    $treasuryUsers = User::where('role', 'tesoreria')->get();
                    foreach($treasuryUsers as $treasury) {
                        $treasury->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio} corregido y reenviado a Tesorería.", "info"));
                    }
                } else {
                    // Notify Treasury
                    $treasuryUsers = User::where('role', 'tesoreria')->get();
                    foreach($treasuryUsers as $treasury) {
                        $treasury->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio} revisado por CXP, pendiente de pago.", "warning"));
                    }
                    // Notify Owner
                    if ($owner) {
                        $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} ha sido revisado por CXP.", "info"));
                    }
                }
            } elseif ($data['status'] === 'aprobado') {
                if ($owner) {
                    $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} ha sido aprobado finalmente y está listo para pago.", "success"));
                }
            } elseif ($data['status'] === 'rechazado') {
                if ($owner) {
                    $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} ha sido rechazado definitivamente.", "danger"));
                }
            } elseif ($data['status'] === 'requiere_correccion') {
                if ($owner) {
                    $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} requiere corrección.", "warning"));
                }
            } elseif ($data['status'] === 'pendiente' && $isOwnerCorrecting) {
                // Resubmitted to Director
                if ($reimbursement->costCenter && $reimbursement->costCenter->director) {
                    $reimbursement->costCenter->director->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio} corregido y reenviado.", "info"));
                }
            }
        }

        return back()->with('success', 'Actualización guardada con éxito.');
    }

    public function validatePdfCorrection(Request $request, Reimbursement $reimbursement)
    {
        set_time_limit(120); // Increase time limit for slow PDF parsing

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf',
        ]);

        if (empty($reimbursement->uuid)) {
            return response()->json(['error' => 'Este reembolso original no contiene un UUID XML.'], 422);
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($request->file('pdf_file')->getRealPath());
            $text = $pdf->getText();
            $cleanText = str_replace([' ', "\n", "\r", "\t"], '', $text);
            
            // UUID Check
            $uuidFound = stripos($text, (string)$reimbursement->uuid) !== false || stripos($cleanText, (string)$reimbursement->uuid) !== false;
            
            // Total Check
            $total = $reimbursement->total;
            $totalFormatted = number_format((float)$total, 2);
            $totalRaw = $total;
            $totalFound = str_contains($text, (string)$totalFormatted) || str_contains($text, (string)$totalRaw) || str_contains($cleanText, (string)$totalFormatted) || str_contains($cleanText, (string)$totalRaw);

            $validationData = [
                'uuid_match' => $uuidFound,
                'total_match' => $totalFound,
                'message' => $uuidFound ? 'UUID encontrado en PDF.' : 'Advertencia: UUID NO encontrado en PDF.',
            ];

            return response()->json($validationData);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo leer el PDF: ' . $e->getMessage()], 422);
        }
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
    private function getCategories()
    {
        return [
            'combustibles y lubricantes',
            'estacionamientos y casetas',
            'hospedajes',
            'comidas',
            'papelería y articulos de oficina',
            'herramientas y refacciones',
            'materiales diversos',
            'diversos',
            'anuncios y revistas',
            'infracciones y multas',
            'pasajes',
            'sueldos de obra',
            'renta local',
            'agua',
            'envios y paquetería',
            'medicinas y doctor',
            'viaticos',
            'renta de maquinaria y transporte',
            'mantenimiento y servicios de equipo y transporte',
            'supervisión',
            'fletes y acarreo',
            'luz',
            'deducibles autos dañados',
            'vigilancia',
            'mantenimiento de oficina',
            'reparación y mantenimiento de equipo de oficina',
            'utensilios auxiliares para trabajo',
            'telefonia y radio',
        ];
    }
    /**
     * View a file (XML or PDF) in the browser.
     */
    public function viewFile(Reimbursement $reimbursement, $type)
    {
        if ($type === 'xml') {
            if (!$reimbursement->xml_path || !Storage::exists($reimbursement->xml_path)) {
                abort(404, 'Archivo XML no encontrado.');
            }
            return response()->file(Storage::path($reimbursement->xml_path), [
                'Content-Type' => 'text/xml',
                'Content-Disposition' => 'inline; filename="' . basename($reimbursement->xml_path) . '"'
            ]);
        } elseif ($type === 'pdf') {
            if (!$reimbursement->pdf_path || !Storage::exists($reimbursement->pdf_path)) {
                abort(404, 'Archivo PDF no encontrado.');
            }
            return response()->file(Storage::path($reimbursement->pdf_path), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($reimbursement->pdf_path) . '"'
            ]);
        }
        
        abort(404);
    }

    /**
     * Download all files as a ZIP archive.
     */
    public function downloadZip(Reimbursement $reimbursement)
    {
        $reimbursement->load(['files', 'children']);
        
        // ZIP Filename: Folio_Type_Date_Time.zip
        // If folio is missing (e.g. wrapper trip), use UUID or ID
        $identifier = $reimbursement->folio ?? ($reimbursement->uuid ?? 'ID-' . $reimbursement->id);
        $date = now()->format('Ymd_His');
        $zipName = "{$identifier}_{$reimbursement->type}_{$date}.zip";
        
        return response()->streamDownload(function () use ($reimbursement) {
            // ZipStream v3 - Disable headers as Laravel handles them
            $zip = new \ZipStream\ZipStream(sendHttpHeaders: false);
            
            // Add main files
            if ($reimbursement->xml_path && Storage::exists($reimbursement->xml_path)) {
                $zip->addFileFromPath(basename($reimbursement->xml_path), Storage::path($reimbursement->xml_path));
            }
            
            if ($reimbursement->pdf_path && Storage::exists($reimbursement->pdf_path)) {
                $zip->addFileFromPath(basename($reimbursement->pdf_path), Storage::path($reimbursement->pdf_path));
            }
            
            // Add extra files (Internacional)
            foreach($reimbursement->files as $file) {
                if (Storage::exists($file->file_path)) {
                    $zip->addFileFromPath('extras/' . $file->original_name, Storage::path($file->file_path));
                }
            }
            
            // Add children files (Nacional)
            foreach($reimbursement->children as $child) {
                if ($child->xml_path && Storage::exists($child->xml_path)) {
                    $zip->addFileFromPath('gastos/' . basename($child->xml_path), Storage::path($child->xml_path));
                }
                if ($child->pdf_path && Storage::exists($child->pdf_path)) {
                    $zip->addFileFromPath('gastos/' . basename($child->pdf_path), Storage::path($child->pdf_path));
                }
            }
            
            // Create a text file with verification info
            $info = "Información del Reembolso\n";
            $info .= "Folio: " . ($reimbursement->folio ?? 'N/A') . "\n";
            $info .= "UUID: " . ($reimbursement->uuid ?? 'N/A') . "\n";
            $info .= "Tipo: " . $reimbursement->type . "\n";
            $info .= "Fecha Descarga: " . now()->toDateTimeString() . "\n";
            
            // Simple verification logic (naïve) - check if files exist
            $xmlExists = $reimbursement->xml_path && Storage::exists($reimbursement->xml_path) ? 'SI' : 'NO';
            $pdfExists = $reimbursement->pdf_path && Storage::exists($reimbursement->pdf_path) ? 'SI' : 'NO';
            
            $info .= "\nVerificación de Archivos:\n";
            $info .= "XML Presente: $xmlExists\n";
            $info .= "PDF Presente: $pdfExists\n";
            
            // Check cross-reference if possible (this is hard without re-parsing, but we can state what we have)
            if ($xmlExists === 'SI' && $pdfExists === 'SI') {
                 $info .= "Nota: Se recomienda validar manualmente que el contenido del PDF corresponda al XML.\n";
            }
            
            $zip->addFile('info.txt', $info);
            
        }, $zipName, ['Content-Type' => 'application/zip']);
    }

    /**
     * Manually trigger validation for stored files.
     */
    public function validateStoredFiles(Reimbursement $reimbursement)
    {
        if (!$reimbursement->xml_path || !Storage::exists($reimbursement->xml_path)) {
            return back()->with('error', 'No se encontró el archivo XML para validar.');
        }

        try {
            // Parse XML
            $xmlContent = Storage::get($reimbursement->xml_path);
            $xmlData = $this->extractXmlData($xmlContent);
            $uuid = $xmlData['uuid'];
            $total = $xmlData['total'];

            $pdfValidation = [];

            // Parse PDF if exists
            if ($reimbursement->pdf_path && Storage::exists($reimbursement->pdf_path)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile(Storage::path($reimbursement->pdf_path));
                    $text = $pdf->getText();
                    
                    // Simple cleaning
                    $cleanText = str_replace([' ', "\n", "\r", "\t"], '', $text); 
                    
                    $uuidFound = str_contains($text, $uuid);
                    
                    $totalFormatted = number_format((float)$total, 2);
                    $totalRaw = $total;
                    $totalFound = str_contains($text, $totalFormatted) || str_contains($text, $totalRaw);

                    $pdfValidation = [
                        'uuid_match' => $uuidFound,
                        'total_match' => $totalFound,
                        'message' => $uuidFound ? 'Validación Manual: UUID encontrado.' : 'Validación Manual: UUID NO encontrado.',
                    ];
                } catch (\Exception $e) {
                    $pdfValidation = ['error' => 'Error al leer PDF: ' . $e->getMessage()];
                }
            } else {
                 $pdfValidation = ['message' => 'No hay PDF para validar.'];
            }

            // Update record
            $reimbursement->validation_data = $pdfValidation;
            $reimbursement->save();

            return back()->with('success', 'Validación completada y guardada.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error durante la validación: ' . $e->getMessage());
        }
    }
}
