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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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
        $tab = $request->input('tab', 'active');
        $query = Reimbursement::orderBy('created_at', 'desc');

        // Permissions & Stage Visibility Logic
        if ($user->isAdmin()) {
            if ($tab === 'active') {
                $query->whereNotIn('status', ['aprobado', 'rechazado']);
            } else {
                $query->whereIn('status', ['aprobado', 'rechazado']);
            }
        } elseif ($user->isTreasury()) {
            if ($tab === 'active') {
                $query->where('status', 'aprobado_cxp');
            } else {
                // History: Approved by CXP once, but not currently in Treasury's active hand
                $query->whereNotNull('approved_by_cxp_at')
                      ->where('status', '!=', 'aprobado_cxp');
            }
        } elseif ($user->isCxp()) {
            if ($tab === 'active') {
                $query->where('status', 'aprobado_ejecutivo');
            } else {
                // History: Approved by Executive once, but not currently in CXP's active hand
                $query->whereNotNull('approved_by_executive_at')
                      ->where('status', '!=', 'aprobado_ejecutivo');
            }
        } elseif ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
            $query->where(function($q) use ($user, $tab) {
                // 1. Visibilidad como Creador (Dueño)
                $q->where(function($ownerQuery) use ($user, $tab) {
                    $ownerQuery->where('user_id', $user->id);
                    if ($tab === 'active') {
                        $ownerQuery->whereNotIn('status', ['aprobado', 'rechazado']);
                    } else {
                        $ownerQuery->whereIn('status', ['aprobado', 'rechazado']);
                    }
                });

                // 2. Visibilidad como Aprobador (Rol designado en el Centro de Costos)
                $q->orWhere(function($approverQuery) use ($user, $tab) {
                    // Primero, restringir por Centro de Costos donde soy el aprobador designado
                    $approverQuery->whereHas('costCenter', function($cc) use ($user) {
                        if ($user->isDirector()) $cc->where('director_id', $user->id);
                        if ($user->isControlObra()) $cc->where('control_obra_id', $user->id);
                        if ($user->isExecutiveDirector()) $cc->where('director_ejecutivo_id', $user->id);
                    });

                    if ($tab === 'active') {
                        // En "Activos", solo veo lo que me toca aprobar ahora mismo
                        if ($user->isDirector()) $approverQuery->where('status', 'pendiente');
                        if ($user->isControlObra()) $approverQuery->where('status', 'aprobado_director');
                        if ($user->isExecutiveDirector()) $approverQuery->where('status', 'aprobado_control');
                    } else {
                        // En "Historial", veo lo que ya pasó por mis manos o fue rechazado/corregido
                        if ($user->isDirector()) {
                            $approverQuery->where('status', '!=', 'pendiente');
                        }
                        if ($user->isControlObra()) {
                            $approverQuery->whereNotNull('approved_by_director_at')
                                         ->where('status', '!=', 'aprobado_director');
                        }
                        if ($user->isExecutiveDirector()) {
                            $approverQuery->whereNotNull('approved_by_control_at')
                                         ->where('status', '!=', 'aprobado_control');
                        }
                    }
                });
            });
        }
 else {
            // Standard Users see only their own
            $query->where('user_id', $user->id);
            if ($tab === 'active') {
                $query->whereNotIn('status', ['aprobado', 'rechazado']);
            } else {
                $query->whereIn('status', ['aprobado', 'rechazado']);
            }
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isCxp() || $user->isTreasury()) {
            abort(403, 'Tu rol (CXP/Tesorería) no tiene permisos para crear reembolsos, solo para aprobarlos y pagarlos.');
        }

        $type = $request->get('type', 'reembolso');
        $allowedTypes = ['reembolso', 'fondo_fijo', 'comida', 'viaje'];

        if (!$type || !in_array($type, $allowedTypes)) {
            return view('reimbursements.select_type');
        }

        $user = Auth::user();
        
        // Filter cost centers based on role
        if ($user->isAdmin()) {
            $costCenters = CostCenter::orderBy('name')->get();
        } elseif ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
            $costCenters = CostCenter::where(function($q) use ($user) {
                if ($user->isDirector()) $q->orWhere('director_id', $user->id);
                if ($user->isControlObra()) $q->orWhere('control_obra_id', $user->id);
                if ($user->isExecutiveDirector()) $q->orWhere('director_ejecutivo_id', $user->id);
            })->orderBy('name')->get();
        } else {
            // Standard users see all for now (unless specified otherwise)
            $costCenters = CostCenter::orderBy('name')->get();
        }
        
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
     * Store multiple resources in storage.
     */
    public function bulkStore(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isCxp() || $user->isTreasury()) {
            abort(403, 'Tu rol no tiene permisos para registrar reembolsos.');
        }

        $request->validate([
            'type' => 'required|in:reembolso,fondo_fijo,comida',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'week' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.category' => ['required', Rule::in($this->getCategories())],
            'items.*.xml_file' => 'required|file|mimes:xml',
            'items.*.pdf_file' => 'nullable|file|mimes:pdf',
            'items.*.confirm_company' => 'required',
        ]);

        set_time_limit(300); // Higher limit for bulk processing

        $type = $request->type;
        $costCenterId = $request->cost_center_id;
        $week = $request->week;
        $costCenter = CostCenter::find($costCenterId);

        // Ownership Validation: N1, N2, N3 can only register in their own cost centers
        if ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
            $isAuthorized = false;
            if ($user->isDirector() && $costCenter->director_id === $user->id) $isAuthorized = true;
            if ($user->isControlObra() && $costCenter->control_obra_id === $user->id) $isAuthorized = true;
            if ($user->isExecutiveDirector() && $costCenter->director_ejecutivo_id === $user->id) $isAuthorized = true;

            if (!$isAuthorized && !$user->isAdmin()) {
                abort(403, 'No tienes permiso para registrar gastos en este centro de costos.');
            }
        }

        // Auto-approval logic based on creator's role
        $initialStatus = 'pendiente';
        $autoNote = "";
        $approvalData = [];

        if ($user->isExecutiveDirector()) {
            $initialStatus = 'aprobado_ejecutivo';
            $autoNote = "\n[AUTO-APROBACIÓN SISTEMA: Ingresado por Director Ejecutivo]";
            $approvalData = [
                'approved_by_director_id' => $user->id,
                'approved_by_director_at' => now(),
                'approved_by_control_id' => $user->id,
                'approved_by_control_at' => now(),
                'approved_by_executive_id' => $user->id,
                'approved_by_executive_at' => now(),
            ];
        } elseif ($user->isControlObra()) {
            $initialStatus = 'aprobado_control';
            $autoNote = "\n[AUTO-APROBACIÓN SISTEMA: Ingresado por Control de Obra]";
            $approvalData = [
                'approved_by_director_id' => $user->id,
                'approved_by_director_at' => now(),
                'approved_by_control_id' => $user->id,
                'approved_by_control_at' => now(),
            ];
        } elseif ($user->isDirector()) {
            $initialStatus = 'aprobado_director';
            $autoNote = "\n[AUTO-APROBACIÓN SISTEMA: Ingresado por Director]";
            $approvalData = [
                'approved_by_director_id' => $user->id,
                'approved_by_director_at' => now(),
            ];
        }

        $createdCount = 0;
        $failedCount = 0;
        $errors = [];
        $processedUuids = [];

        foreach ($request->items as $index => $item) {
            try {
                $xmlContent = file_get_contents($item['xml_file']->getRealPath());
                $xmlData = $this->extractXmlData($xmlContent);
                $uuid = $xmlData['uuid'];

                // Check for duplicate in DB or current batch
                if (in_array($uuid, $processedUuids) || Reimbursement::where('uuid', $uuid)->exists()) {
                    $errors[] = "Ítem #" . ($index + 1) . ": El CFDI con UUID {$uuid} ya está registrado o duplicado en esta carga.";
                    $failedCount++;
                    continue;
                }
                $processedUuids[] = $uuid;
                
                $pdfFile = $item['pdf_file'] ?? null;
                $validationData = $this->getValidationData($xmlData, $pdfFile);

                $xmlPath = $item['xml_file']->store('xmls');
                $pdfPath = $pdfFile ? $pdfFile->store('pdfs') : null;

                $finalObs = ($item['observaciones'] ?? "") . $autoNote;

                $reimbursementData = array_merge([
                    'type' => $type,
                    'cost_center_id' => $costCenterId,
                    'week' => $week,
                    'category' => $item['category'],
                    'uuid' => $uuid,
                    'rfc_emisor' => $xmlData['rfc_emisor'],
                    'nombre_emisor' => $xmlData['nombre_emisor'],
                    'rfc_receptor' => $xmlData['rfc_receptor'],
                    'nombre_receptor' => $xmlData['nombre_receptor'],
                    'folio' => $xmlData['folio'], 
                    'fecha' => $xmlData['fecha'],
                    'total' => $xmlData['total'],
                    'subtotal' => $xmlData['subtotal'],
                    'moneda' => $xmlData['moneda'],
                    'tipo_comprobante' => $xmlData['tipo_comprobante'],
                    'xml_path' => $xmlPath,
                    'pdf_path' => $pdfPath,
                    'status' => $initialStatus,
                    'observaciones' => trim($finalObs),
                    'attendees_count' => $item['attendees_count'] ?? null,
                    'attendees_names' => $item['attendees_names'] ?? null,
                    'location' => $item['location'] ?? null,
                    'user_id' => $user->id,
                    'company_confirmed' => true,
                    'validation_data' => $validationData, // Saving validation for the table
                ], $approvalData);

                $reimbursement = Reimbursement::create($reimbursementData);

                $prefix = strtoupper(substr($type, 0, 3));
                $reimbursement->folio = $prefix . '-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT);
                $reimbursement->save();

                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = "Ítem #" . ($index + 1) . ": Error al procesar - " . $e->getMessage();
                $failedCount++;
                Log::error("Bulk store error: " . $e->getMessage());
                continue;
            }
        }

        // Notify NEXT person in line
        if ($createdCount > 0 && $costCenter) {
            $targetUser = null;
            $notifMsg = "Se cargaron {$createdCount} reembolsos. Revísalos en tu listado de reembolsos.";

            if ($initialStatus === 'aprobado_ejecutivo') {
                // To CXP
                $cxpUsers = User::where('role', 'accountant')->get();
                foreach($cxpUsers as $cxp) {
                    $cxp->notify(new ReimbursementNotification(null, $notifMsg, "info"));
                }
            } elseif ($initialStatus === 'aprobado_control') {
                // To N3
                $targetUser = $costCenter->directorEjecutivo;
            } elseif ($initialStatus === 'aprobado_director') {
                // To N2
                $targetUser = $costCenter->controlObra;
            } else {
                // Standard case: To N1
                $targetUser = $costCenter->director;
            }

            if ($targetUser) {
                $targetUser->notify(new ReimbursementNotification(null, $notifMsg, "info"));
            }
        }

        $message = "Se han creado {$createdCount} reembolsos exitosamente.";
        if ($failedCount > 0) {
            $message .= " Sin embargo, {$failedCount} no pudieron procesarse: " . implode(' ', $errors);
            return redirect()->route('reimbursements.index')->with('warning', $message);
        }

        return redirect()->route('reimbursements.index')->with('success', $message);
    }





    /**
     * Parse XML and return data as JSON.
     */
    public function parseCfdi(Request $request)
    {
        set_time_limit(120);

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

            $pdfFile = $request->file('pdf_file');
            $pdfValidation = $this->getValidationData($data, $pdfFile);

            return response()->json(array_merge($data, ['pdf_validation' => $pdfValidation]));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar el XML: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Helper to perform PDF/XML cross-validation.
     */
    private function getValidationData($xmlData, $pdfFile)
    {
        if (!$pdfFile) return null;

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfFile->getRealPath());
            $text = $pdf->getText();
            
            // UUID Check
            $uuidFound = str_contains($text, $xmlData['uuid']);
            
            // Total Check
            $total = $xmlData['total'];
            $totalFormatted = number_format((float)$total, 2);
            $totalRaw = $total;
            
            $totalFound = str_contains($text, $totalFormatted) || str_contains($text, $totalRaw);

            return [
                'uuid_match' => $uuidFound,
                'total_match' => $totalFound,
                'message' => $uuidFound ? 'Validación exitosa.' : 'Advertencia de UUID.',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Lectura de PDF fallida: ' . $e->getMessage()];
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isCxp() || $user->isTreasury()) {
            abort(403, 'Tu rol no tiene permisos para registrar reembolsos.');
        }

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

            // Duplicity check
            if (Reimbursement::where('uuid', $request->uuid)->exists()) {
                return back()->withInput()->with('error', 'Atención: Este CFDI (UUID: ' . $request->uuid . ') ya se encuentra registrado en el sistema.');
            }
        }
        // But the previous prompts imply "Viaje" is a container.
        // Let's adjust validation for XML/PDF to be nullable if type is viaje
        


        $xmlPath = $request->file('xml_file') ? $request->file('xml_file')->store('xmls') : null;
        $pdfPath = $request->file('pdf_file') ? $request->file('pdf_file')->store('pdfs') : null;

        // Ownership Validation
        $costCenter = CostCenter::findOrFail($request->cost_center_id);
        if ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
            $isAuthorized = false;
            if ($user->isDirector() && $costCenter->director_id === $user->id) $isAuthorized = true;
            if ($user->isControlObra() && $costCenter->control_obra_id === $user->id) $isAuthorized = true;
            if ($user->isExecutiveDirector() && $costCenter->director_ejecutivo_id === $user->id) $isAuthorized = true;

            if (!$isAuthorized && !$user->isAdmin()) {
                abort(403, 'No tienes permiso para registrar gastos en este centro de costos.');
            }
        }

        // Auto-approval logic
        $initialStatus = 'pendiente';
        $autoNote = "";
        $approvalData = [];

        if ($user->isExecutiveDirector()) {
            $initialStatus = 'aprobado_ejecutivo';
            $autoNote = "\n[AUTO-APROBACIÓN SISTEMA: Ingresado por Director Ejecutivo]";
            $approvalData = [
                'approved_by_director_id' => $user->id,
                'approved_by_director_at' => now(),
                'approved_by_control_id' => $user->id,
                'approved_by_control_at' => now(),
                'approved_by_executive_id' => $user->id,
                'approved_by_executive_at' => now(),
            ];
        } elseif ($user->isControlObra()) {
            $initialStatus = 'aprobado_control';
            $autoNote = "\n[AUTO-APROBACIÓN SISTEMA: Ingresado por Control de Obra]";
            $approvalData = [
                'approved_by_director_id' => $user->id,
                'approved_by_director_at' => now(),
                'approved_by_control_id' => $user->id,
                'approved_by_control_at' => now(),
            ];
        } elseif ($user->isDirector()) {
            $initialStatus = 'aprobado_director';
            $autoNote = "\n[AUTO-APROBACIÓN SISTEMA: Ingresado por Director]";
            $approvalData = [
                'approved_by_director_id' => $user->id,
                'approved_by_director_at' => now(),
            ];
        }

        $finalObs = ($request->observaciones ?? "") . $autoNote;

        $reimbursementData = array_merge([
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
            'status' => $initialStatus,
            'observaciones' => trim($finalObs),
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
        ], $approvalData);

        $reimbursement = Reimbursement::create($reimbursementData);

        // Generate Custom Folio: XXX-000000
        // Type Map: REE, FON, COM, VIA
        $prefix = strtoupper(substr($request->type, 0, 3));
        $reimbursement->folio = $prefix . '-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT);
        $reimbursement->save();

        // Notify NEXT person in line
        if ($reimbursement->costCenter) {
            $notifMsg = "Nueva solicitud ({$reimbursement->folio}) pendiente de tu aprobación.";
            $targetUser = null;

            if ($initialStatus === 'aprobado_ejecutivo') {
                $cxpUsers = User::where('role', 'accountant')->get();
                foreach($cxpUsers as $cxp) {
                    $cxp->notify(new ReimbursementNotification($reimbursement, $notifMsg, "info"));
                }
            } elseif ($initialStatus === 'aprobado_control') {
                $targetUser = $reimbursement->costCenter->directorEjecutivo;
            } elseif ($initialStatus === 'aprobado_director') {
                $targetUser = $reimbursement->costCenter->controlObra;
            } else {
                $targetUser = $reimbursement->costCenter->director;
            }

            if ($targetUser) {
                $targetUser->notify(new ReimbursementNotification($reimbursement, $notifMsg, "info"));
            }
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
                         ->with('success', 'Viaje creado exitosamente.');
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
        $user = Auth::user();

        // Admin & Owner always see
        if ($user->isAdmin() || $user->id === $reimbursement->user_id) {
            return view('reimbursements.show', compact('reimbursement'));
        }

        // Strict Sequential Visibility
        $cc = $reimbursement->costCenter;
        $status = $reimbursement->status;

        $canSee = false;
        if ($user->isDirector() && $cc->director_id === $user->id) {
            // Directors see everything once submitted (Level 1)
            $canSee = true; 
        } elseif ($user->isControlObra() && $cc->control_obra_id === $user->id) {
            // Level 2 sees if it reached them once (even if currently in correction/rejected)
            // They see if status != pendiente OR if there's any record of moving past N1
            $canSee = $reimbursement->approved_by_director_at !== null || !in_array($status, ['pendiente', 'requiere_correccion']);
        } elseif ($user->isExecutiveDirector() && $cc->director_ejecutivo_id === $user->id) {
            // Level 3 sees if it reached them once
            $canSee = $reimbursement->approved_by_control_at !== null || !in_array($status, ['pendiente', 'requiere_correccion', 'aprobado_director']);
        } elseif ($user->isCxp()) {
            // CXP sees if it reached them once
            $canSee = $reimbursement->approved_by_executive_at !== null || !in_array($status, ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control']);
        } elseif ($user->isTreasury()) {
            // Treasury sees if it reached them once
            $canSee = $reimbursement->approved_by_cxp_at !== null || !in_array($status, ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo']);
        }

        if (!$canSee) {
            abort(403, 'Aún no tienes permiso para ver este reembolso. Está en una etapa anterior de aprobación.');
        }

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
                      ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id) ||
                      ($user->isControlObra() && $reimbursement->costCenter->control_obra_id === $user->id) ||
                      ($user->isExecutiveDirector() && $reimbursement->costCenter->director_ejecutivo_id === $user->id);

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

            // Direct routing rule: Return to the level that was currently reviewing it
            if ($reimbursement->approved_by_cxp_id !== null) {
                $data['status'] = 'aprobado_cxp'; // Back to Treasury
            } elseif ($reimbursement->approved_by_executive_id !== null) {
                $data['status'] = 'aprobado_ejecutivo'; // Back to CXP
            } elseif ($reimbursement->approved_by_control_id !== null) {
                $data['status'] = 'aprobado_control'; // Back to Executive
            } elseif ($reimbursement->approved_by_director_id !== null) {
                $data['status'] = 'aprobado_director'; // Back to Control
            } else {
                $data['status'] = 'pendiente'; // Back to Director
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
                // SEQUENTIAL APPROVAL LOGIC
                
                // 1. Director
                if ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id && $reimbursement->status === 'pendiente') {
                    $data['status'] = 'aprobado_director';
                    $data['approved_by_director_id'] = $user->id;
                    $data['approved_by_director_at'] = now();
                }

                // 2. Control de Obra
                if ($user->isControlObra() && $reimbursement->costCenter->control_obra_id === $user->id && $reimbursement->status === 'aprobado_director') {
                    $data['status'] = 'aprobado_control';
                    $data['approved_by_control_id'] = $user->id;
                    $data['approved_by_control_at'] = now();
                }

                // 3. Director Ejecutivo
                if ($user->isExecutiveDirector() && $reimbursement->costCenter->director_ejecutivo_id === $user->id && $reimbursement->status === 'aprobado_control') {
                    $data['status'] = 'aprobado_ejecutivo';
                    $data['approved_by_executive_id'] = $user->id;
                    $data['approved_by_executive_at'] = now();
                }

                // 4. CXP (Cuentas por Pagar)
                if ($user->isCxp() && $reimbursement->status === 'aprobado_ejecutivo') {
                    $data['status'] = 'aprobado_cxp';
                    $data['approved_by_cxp_id'] = $user->id;
                    $data['approved_by_cxp_at'] = now();
                }

                // 5. Tesorería (Final)
                if (($user->isTreasury() || $user->isAdmin()) && $reimbursement->status === 'aprobado_cxp') {
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
                $target = $reimbursement->costCenter->controlObra;
                if ($target) $target->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de Control de Obra.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Director, pasa a Control de Obra.", "info"));
            } elseif ($data['status'] === 'aprobado_control') {
                $target = $reimbursement->costCenter->directorEjecutivo;
                if ($target) $target->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de Director Ejecutivo.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Control de Obra, pasa a Dir. Ejecutivo.", "info"));
            } elseif ($data['status'] === 'aprobado_ejecutivo') {
                $cxpUsers = User::where('role', 'accountant')->get();
                foreach($cxpUsers as $cxp) $cxp->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de CXP.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Dir. Ejecutivo, pasa a CXP.", "info"));
            } elseif ($data['status'] === 'aprobado_cxp') {
                $treasuryUsers = User::where('role', 'tesoreria')->get();
                foreach($treasuryUsers as $treasury) $treasury->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: aprobado por CXP, pendiente de pago.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} revisado por CXP.", "info"));
            } elseif ($data['status'] === 'aprobado') {
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado finalmente.", "success"));
            } elseif ($data['status'] === 'rechazado') {
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} RECHAZADO.", "danger"));
            } elseif ($data['status'] === 'requiere_correccion') {
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} requiere corrección.", "warning"));
            } elseif ($data['status'] === 'pendiente' && $isOwnerCorrecting) {
                if ($reimbursement->costCenter && $reimbursement->costCenter->director) {
                    $reimbursement->costCenter->director->notify(new ReimbursementNotification($reimbursement, "Reembolso corregido y reenviado.", "info"));
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
