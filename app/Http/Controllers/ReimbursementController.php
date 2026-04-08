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
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

class ReimbursementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canManage = $user->isAdmin() || $user->isAdminView() || $user->isCxp() || $user->isTreasury() || $user->isDireccion() || $user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector();
        $tab = $request->input('tab', $canManage ? 'management' : 'active');
        $globalSearch = $request->input('global_search');
        
        $query = Reimbursement::with(['user', 'costCenter'])->orderBy('created_at', 'desc');

        // TRACKER LOGIC: Global search bypasses tab scoping for management roles
        if ($globalSearch && $canManage) {
            $query->where(function($q) use ($globalSearch) {
                $q->where('folio', 'like', "%{$globalSearch}%")
                  ->orWhere('uuid', 'like', "%{$globalSearch}%");
            });
            $reimbursements = $query->paginate(10)->appends($request->all());
            return view('reimbursements.index', compact('reimbursements', 'globalSearch'));
        }

        // Apply Tab Scoping
        $this->applyTabScope($query, $tab, $user);

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

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $fromDate = $request->from_date;
            $toDate = $request->to_date;

            $query->where(function($q) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $q->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                      ->orWhereBetween('fecha', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $q->whereDate('created_at', '>=', $fromDate)
                      ->orWhereDate('fecha', '>=', $fromDate);
                } elseif ($toDate) {
                    $q->whereDate('created_at', '<=', $toDate)
                      ->orWhereDate('fecha', '<=', $toDate);
                }
            });
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['folio', 'fecha', 'nombre_emisor', 'total', 'status', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->reorder($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->reorder('created_at', 'desc');
        }

        if ($tab === 'management' || $tab === 'weekly_summary' || $tab === 'active' || $tab === 'history' || $tab === 'global_history') {
            // Paginate by weeks (5 weeks per page)
            $weeksQuery = clone $query;
            $weeksPaginator = $weeksQuery->reorder()
                ->select('week')
                ->groupBy('week')
                ->orderByRaw("SUBSTRING_INDEX(week, '-', -1) DESC")
                ->orderByRaw("SUBSTRING_INDEX(week, '-', 1) DESC")
                ->paginate(5, ['*'], 'page')
                ->withQueryString();

            $currentWeeks = $weeksPaginator->pluck('week');
            $reimbursements = $query->whereIn('week', $currentWeeks)->get();
            
            return view('reimbursements.index', compact('reimbursements', 'globalSearch', 'weeksPaginator'));
        }

        $reimbursements = $query->paginate(10)->appends($request->all());
        return view('reimbursements.index', compact('reimbursements', 'globalSearch'));
    }

    /**
     * Display the Auditoría de Reembolsos as a standalone page.
     */
    public function audit(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canManage = $user->isAdmin() || $user->isAdminView() || $user->isCxp() || $user->isTreasury() || $user->isDireccion() || $user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector();

        if (!$canManage) {
            abort(403, 'No tienes permiso para ver la auditoría.');
        }

        // Load reimbursements based on tab scope
        $tab = $request->input('tab', 'management');
        $query = Reimbursement::with(['user', 'costCenter'])->orderBy('created_at', 'desc');
        $this->applyTabScope($query, $tab, $user);

        $allReimbursements = $query->get();

        // Filter parameters from the request
        $selectedWeek    = $request->input('week');
        $selectedCcName  = $request->input('cc');
        $selectedType    = $request->input('type');

        // Build stats for dashboard panels
        $auditStats = null;
        if ($selectedWeek) {
            $itemsForStats = $allReimbursements->where('week', $selectedWeek);
            if ($selectedCcName) {
                $itemsForStats = $itemsForStats->filter(fn($r) => ($r->costCenter->name ?? 'Sin Centro de Costos') === $selectedCcName);
            }
            if ($selectedType) {
                $itemsForStats = $itemsForStats->where('type', $selectedType);
            }

            if ($itemsForStats->count() > 0) {
                $topSolicitor = $itemsForStats->groupBy('user_id')
                    ->map(fn($group) => ['user' => $group->first()->user->name ?? 'N/A', 'total' => $group->sum('total')])
                    ->sortByDesc('total')
                    ->first();

                $auditStats = [
                    'total' => $itemsForStats->sum('total'),
                    'count' => $itemsForStats->count(),
                    'avg' => $itemsForStats->avg('total'),
                    'status_counts' => $itemsForStats->groupBy('status')->map->count(),
                    'category_totals' => $itemsForStats->groupBy('category')->map->sum('total'),
                    'validation_passed' => $itemsForStats->filter(fn($r) => ($r->validation_data['uuid_match'] ?? false) && ($r->validation_data['total_match'] ?? false))->count(),
                    'manual_count' => $itemsForStats->where('folio', 'SIN-FACTURA')->count(),
                    'top_solicitor' => $topSolicitor,
                ];
            }
        }

        // Build grouped data
        $groupedByWeek = $allReimbursements->groupBy('week');

        // If all three params exist, resolve the specific items
        $auditItems = null;
        $auditMeta  = null;

        if ($selectedWeek && $selectedCcName && $selectedType) {
            $weekItems = $groupedByWeek->get($selectedWeek, collect());
            $ccItems   = $weekItems->filter(fn($r) => ($r->costCenter->name ?? 'Sin Centro de Costos') === $selectedCcName);
            $typeItems = $ccItems->where('type', $selectedType);

            $auditItems = $typeItems->sortByDesc('fecha');
            $auditMeta  = [
                'week'    => $selectedWeek,
                'cc_name' => $selectedCcName,
                'type'    => $selectedType,
                'total'   => $typeItems->sum('total'),
                'count'   => $typeItems->count(),
            ];
        }

        return view('reimbursements.audit', compact('groupedByWeek', 'auditItems', 'auditMeta', 'selectedWeek', 'selectedCcName', 'selectedType', 'auditStats'));
    }


    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        

        if ($user->isCxp() || $user->isTreasury() || $user->isAdminView()) {
            abort(403, 'Tu rol no tiene permisos para crear reembolsos.');
        }

        $type = $request->input('type');
        $hasInvoice = $request->input('has_invoice', '1') !== '0';
        $allowedTypes = ['reembolso', 'fondo_fijo', 'comida', 'viaje'];

        $drafts = Reimbursement::where('user_id', $user->id)
                                ->where('status', 'borrador')
                                ->latest()
                                ->get();

        if (!$type || !in_array($type, $allowedTypes)) {
            $isMarkedInAnyCC = $user->isAdmin() || $user->authorizedCostCenters()->wherePivot('can_do_special', true)->exists();
            return view('reimbursements.select_type', compact('drafts', 'isMarkedInAnyCC'));
        }

        $user = Auth::user();
        
        // Filter cost centers based on role and permissions
        if ($user->isAdmin()) {
            $costCenters = CostCenter::with('beneficiary')->orderBy('name')->get();
        } else {
            // Only show cost centers where the user is authorized
            $costCenters = $user->authorizedCostCenters()->with('beneficiary')->withPivot('can_do_special')->orderBy('name')->get();

            // Filter based on type and "one reimbursement" rule
            $costCenters = $costCenters->filter(function($cc) use ($user, $type) {
                $isMarked = $cc->pivot->can_do_special;

                // Special types (Fondo Fijo, Comida, Viaje) are restricted to marked users
                if (in_array($type, ['fondo_fijo', 'comida', 'viaje'])) {
                    return $isMarked;
                }

                // Standard reimbursement: unmarked users can only have ONE (none pending/approved)
                if (!$isMarked) {
                    $hasExisting = Reimbursement::where('cost_center_id', $cc->id)
                        ->where('user_id', $user->id)
                        ->where('status', '!=', 'rechazado')
                        ->exists();
                    return !$hasExisting;
                }

                return true;
            });
        }
        
        // Auto-fill week: WeekNumber-Year (Starts Saturday, Ends Friday)
        $currentWeek = now()->addDays(2)->format('W-Y');
        
        $categories = $this->getCategories();

        // Load active travel events (filtered by user access unless admin)
        $travelEventsQuery = \App\Models\TravelEvent::where('status', 'active');
        if (!$user->isAdmin() && !$user->isAdminView()) {
            $travelEventsQuery->whereHas('participants', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        $travelEvents = $travelEventsQuery->orderBy('name')->get();

        // Check for parent trip context
        $parentReimbursement = null;
        if ($request->has('trip_id')) {
            $parentReimbursement = Reimbursement::find($request->trip_id);
        }

        return view('reimbursements.create', compact('type', 'costCenters', 'travelEvents', 'currentWeek', 'categories', 'parentReimbursement', 'hasInvoice'));
    }

    /**
     * Store multiple resources in storage.
     */
    public function bulkStore(Request $request)
    {
        $hasInvoice = $request->input('has_invoice', '1') == '1';
        Log::info("BULK_STORE_START: User=" . Auth::id() . " Mode=" . ($hasInvoice ? 'Invoice' : 'Manual'));

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isCxp() || $user->isTreasury() || $user->isAdminView()) {
            abort(403, 'Tu rol no tiene permisos para registrar reembolsos.');
        }


        $rules = [
            'type' => 'required|in:reembolso,fondo_fijo,comida,viaje',
            'cost_center_id' => 'required_without:travel_event_id|nullable|exists:cost_centers,id',
            'travel_event_id' => 'required_without:cost_center_id|nullable|exists:travel_events,id',
            'week' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.category' => ['required', Rule::in($this->getCategories())],
            'items.*.observaciones' => 'required|string',
            'items.*.xml_file' => $hasInvoice ? 'required_without:items.*.draft_id' : 'nullable',
            'items.*.pdf_file' => $hasInvoice ? 'nullable|file|max:15360' : 'required_without:items.*.draft_id|file|max:15360',
            'items.*.ticket_file' => 'required_without:items.*.draft_id|file|max:10240',
            'items.*.confirm_company' => $hasInvoice ? 'required' : 'nullable',
            'items.*.attendees_count' => 'required_if:type,comida',
            'items.*.location' => 'required_if:type,comida',
            'items.*.attendees_names' => 'required_if:type,comida',
            // Stricter publication fields
            'items.*.uuid' => $hasInvoice ? 'required_without:items.*.draft_id' : 'nullable',
            'items.*.total' => 'required',
            'items.*.fecha' => 'required',
        ];

        if (!$hasInvoice) {
            $rules['items.*.nombre_emisor'] = 'required|string';
            $rules['items.*.fecha'] = 'required|date|before_or_equal:today';
            $rules['items.*.total'] = 'required|numeric|max:2000';
            $rules['items.*.subtotal'] = 'required|numeric|lte:items.*.total';
        }

        $request->validate($rules, [
            'items.*.total.max' => 'No se pueden hacer reembolsos mayores a $2,000 MXN sin factura. Comunícate con tu director.',
            'items.*.fecha.before_or_equal' => 'La fecha de emisión no puede ser una fecha futura.',
            'items.*.subtotal.lte' => 'El subtotal no puede ser mayor al total del comprobante.',
        ]);

        set_time_limit(300); // Higher limit for bulk processing

        $type = $request->type;
        $travelEventId = $request->travel_event_id;
        $travelEvent = $travelEventId ? \App\Models\TravelEvent::find($travelEventId) : null;
        
        // If a travel event is selected, it MUST use its cost center
        $costCenterId = $travelEvent ? $travelEvent->cost_center_id : $request->cost_center_id;
        // AUTO-FILL WEEK: Always use current week on publication
        $week = now()->addDays(2)->format('W-Y');
        
        $costCenter = $costCenterId ? CostCenter::find($costCenterId) : null;
        
        // Payee Logic
        $payeeId = $user->id;
        if (in_array($type, ['fondo_fijo', 'comida'])) {
            $payeeId = $costCenter ? ($costCenter->beneficiary_id ?? $user->id) : $user->id;
        } else {
            $payeeId = $request->input('payee_id', $user->id);
        }

        // If it's a travel event, we might want to use its director as the first approver if CC is null
        // For now, let's keep the dynamic workflow logic based on CC if available, or a fallback.
        
        $currentStepId = null;
        $initialStatus = 'pendiente';
        $approvalData = [];
        $autoNote = "";

        if ($travelEvent && $travelEvent->status === 'active') {
            // Reembolsos de un evento activo se quedan en pausa hasta que el Aprobador Principal "cierre" el evento.
            $initialStatus = 'en_evento';
            $currentStepId = null;
        } elseif ($costCenter) {
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

            // DYNAMIC WORKFLOW: Initialize at the first step
            $firstStep = $costCenter->approvalSteps()->orderBy('order', 'asc')->first();
            $initialStatus = $firstStep ? 'pendiente' : 'aprobado';
            $currentStepId = $firstStep ? $firstStep->id : null;

            // AUTO-APPROVAL: If creator is the approver, advance
            while ($firstStep && $firstStep->user_id === $user->id) {
                $autoNote .= "\n[AUTO-APROBACIÓN: " . $firstStep->name . "]";
                $this->mapApprovalData($firstStep->order, $user->id, $approvalData);
                
                $nextStep = $costCenter->approvalSteps()->where('order', '>', $firstStep->order)->orderBy('order', 'asc')->first();
                if ($nextStep) {
                    $firstStep = $nextStep;
                    $currentStepId = $nextStep->id;
                } else {
                    $firstStep = null;
                    $currentStepId = null;
                    $initialStatus = 'aprobado';
                    break;
                }
            }
        }

        $createdCount = 0;
        $failedCount = 0;
        $errors = [];
        $processedUuids = [];

        foreach ($request->items as $index => $item) {
            try {
                $costCenterId = $item['cost_center_id'] ?? $request->cost_center_id;
                $travelEventId = $item['travel_event_id'] ?? $request->travel_event_id;

                // Permissions and Limits Check
                if (!$this->canCreateReimbursement($user, $type, $costCenterId)) {
                    throw new \Exception("No tienes permiso para registrar este tipo de reembolso en este Centro de Costos o ya has alcanzado tu límite de 1 reembolso activo.");
                }

                $xmlData = null;
                $uuid = null;
                $xmlPath = null;
                $pdfFile = $item['pdf_file'] ?? null;
                $validationData = null;

                if ($hasInvoice) {
                    if (isset($item['xml_file']) && $item['xml_file'] instanceof \Illuminate\Http\UploadedFile) {
                        $xmlContent = file_get_contents($item['xml_file']->getRealPath());
                        $xmlData = $this->extractXmlData($xmlContent);
                        $uuid = $xmlData['uuid'];
                        $xmlPath = $item['xml_file']->store('xmls');
                    } else {
                        // Fallback if resuming draft without re-uploading XML
                        $draftId = $item['draft_id'] ?? null;
                        if ($draftId) {
                            $existingDraft = Reimbursement::where('id', $draftId)->where('user_id', $user->id)->first();
                            if ($existingDraft && $existingDraft->xml_path) {
                                $xmlContent = Storage::get($existingDraft->xml_path);
                                $xmlData = $this->extractXmlData($xmlContent);
                                $uuid = $xmlData['uuid'];
                                $xmlPath = $existingDraft->xml_path;
                            } else {
                                throw new \Exception("XML es requerido.");
                            }
                        } else {
                             throw new \Exception("XML es requerido.");
                        }
                    }

                    // Check for duplicate in DB or current batch
                    $existing = Reimbursement::where('uuid', $uuid)->first();
                    if ($existing && $existing->status !== 'borrador') {
                        $errors[] = "Ítem #" . ($index + 1) . ": El CFDI con UUID {$uuid} ya está registrado.";
                        $failedCount++;
                        continue;
                    }
                    if (in_array($uuid, $processedUuids)) {
                        $errors[] = "Ítem #" . ($index + 1) . ": El CFDI con UUID {$uuid} está duplicado en esta carga.";
                        $failedCount++;
                        continue;
                    }
                    $processedUuids[] = $uuid;
                    
                    // Check for existing PDF if new one is missing
                    $pdfToValidate = $pdfFile ?: ($existingDraft ? $existingDraft->pdf_path : null);
                    $validationData = $this->getValidationData($xmlData, $pdfToValidate);
                } else {
                    // Manual data
                    $xmlData = [
                        'rfc_emisor' => $item['rfc_emisor'] ?? 'N/A',
                        'nombre_emisor' => $item['nombre_emisor'],
                        'rfc_receptor' => $item['rfc_receptor'] ?? 'N/A',
                        'nombre_receptor' => $item['nombre_receptor'] ?? 'N/A',
                        'folio' => 'SIN-FACTURA',
                        'fecha' => $item['fecha'],
                        'total' => $item['total'],
                        'subtotal' => $item['subtotal'],
                        'moneda' => 'MXN',
                        'tipo_comprobante' => 'I',
                    ];
                }

                // Check for existing draft to reuse files if not re-uploaded
                $draftId = $item['draft_id'] ?? null;
                $existingDraft = null;
                if ($draftId) {
                    $existingDraft = Reimbursement::where('id', $draftId)->where('user_id', $user->id)->where('status', 'borrador')->first();
                } elseif (isset($uuid)) {
                    $existingDraft = Reimbursement::where('uuid', $uuid)->where('user_id', $user->id)->where('status', 'borrador')->first();
                }

                $pdfPath = $pdfFile ? $pdfFile->store('pdfs') : ($existingDraft ? $existingDraft->pdf_path : null);
                $ticketFile = $item['ticket_file'] ?? null;
                $ticketPath = $ticketFile ? $ticketFile->store('tickets') : ($existingDraft ? $existingDraft->ticket_path : null);

                $finalObs = ($item['observaciones'] ?? "") . $autoNote;

                // DATA PRESERVATION: Use draft value if request value is empty
                $reimbursementData = array_merge([
                    'type' => $type,
                    'cost_center_id' => $costCenterId,
                    'travel_event_id' => $travelEventId,
                    'title' => $travelEvent ? $travelEvent->name : ($item['title'] ?? ($existingDraft ? $existingDraft->title : null)),
                    'week' => $week,
                    'category' => !empty($item['category']) ? $item['category'] : ($existingDraft ? $existingDraft->category : 'viaticos'),
                    'uuid' => !empty($uuid) ? $uuid : ($existingDraft ? $existingDraft->uuid : null),
                    'rfc_emisor' => !empty($xmlData['rfc_emisor']) ? $xmlData['rfc_emisor'] : ($existingDraft ? $existingDraft->rfc_emisor : null),
                    'nombre_emisor' => !empty($xmlData['nombre_emisor']) ? $xmlData['nombre_emisor'] : ($existingDraft ? $existingDraft->nombre_emisor : null),
                    'rfc_receptor' => !empty($xmlData['rfc_receptor']) ? $xmlData['rfc_receptor'] : ($existingDraft ? $existingDraft->rfc_receptor : null),
                    'nombre_receptor' => !empty($xmlData['nombre_receptor']) ? $xmlData['nombre_receptor'] : ($existingDraft ? $existingDraft->nombre_receptor : null),
                    'folio' => !empty($xmlData['folio']) ? $xmlData['folio'] : ($existingDraft ? $existingDraft->folio : null),
                    'fecha' => !empty($xmlData['fecha']) ? $xmlData['fecha'] : ($existingDraft ? $existingDraft->fecha : null),
                    'total' => !empty($xmlData['total']) ? $xmlData['total'] : ($existingDraft ? $existingDraft->total : 0),
                    'subtotal' => !empty($xmlData['subtotal']) ? $xmlData['subtotal'] : ($existingDraft ? $existingDraft->subtotal : 0),
                    'impuestos' => !empty($xmlData['impuestos']) ? $xmlData['impuestos'] : ($existingDraft ? $existingDraft->impuestos : 0),
                    'moneda' => !empty($xmlData['moneda']) ? $xmlData['moneda'] : ($existingDraft ? $existingDraft->moneda : 'MXN'),
                    'tipo_comprobante' => !empty($xmlData['tipo_comprobante']) ? $xmlData['tipo_comprobante'] : ($existingDraft ? $existingDraft->tipo_comprobante : null),
                    'xml_path' => $xmlPath,
                    'pdf_path' => $pdfPath,
                    'ticket_path' => $ticketPath,
                    'status' => $initialStatus,
                    'current_step_id' => $currentStepId,
                    'observaciones' => trim($finalObs),
                    'attendees_count' => $item['attendees_count'] ?? ($existingDraft ? $existingDraft->attendees_count : 0),
                    'attendees_names' => $item['attendees_names'] ?? ($existingDraft ? $existingDraft->attendees_names : null),
                    'location' => $item['location'] ?? ($travelEvent ? $travelEvent->location : ($existingDraft ? $existingDraft->location : null)),
                    'trip_type' => $travelEvent ? $travelEvent->trip_type : ($existingDraft ? $existingDraft->trip_type : null),
                    'trip_start_date' => $travelEvent ? $travelEvent->start_date : ($existingDraft ? $existingDraft->trip_start_date : null),
                    'trip_end_date' => $travelEvent ? $travelEvent->end_date : ($existingDraft ? $existingDraft->trip_end_date : null),
                    'user_id' => $user->id,
                    'payee_id' => $payeeId,
                    'company_confirmed' => isset($item['confirm_company']),
                    'validation_data' => $validationData,
                ], $approvalData);

                // FINAL SAFETY CHECK
                if ($hasInvoice && empty($reimbursementData['uuid'])) {
                     throw new \Exception("Faltan datos críticos del XML. Por favor sube el archivo nuevamente.");
                }

                if ($existingDraft) {
                    $existingDraft->update($reimbursementData);
                    $reimbursement = $existingDraft;
                } else {
                    $reimbursement = Reimbursement::create($reimbursementData);
                }

                $prefix = strtoupper(substr($type, 0, 3));
                if (!str_starts_with($reimbursement->folio, $prefix . '-')) {
                    $reimbursement->folio = $prefix . '-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT);
                    $reimbursement->save();
                }

                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = "Ítem #" . ($index + 1) . ": Error al procesar - " . $e->getMessage();
                $failedCount++;
                Log::error("Bulk store error: " . $e->getMessage());
                continue;
            }
        }

        // Notify NEXT person in line
        if ($createdCount > 0 && $costCenter && $initialStatus !== 'en_evento') {
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
            'xml_file' => 'required_without:draft_id|file|nullable',
            'pdf_file' => 'nullable|file',
            'draft_id' => 'required_without:xml_file|exists:reimbursements,id|nullable',
        ]);

        try {
            $xmlContent = null;
            if ($request->hasFile('xml_file')) {
                $xmlContent = file_get_contents($request->file('xml_file')->getRealPath());
            } elseif ($request->draft_id) {
                $reimbursement = Reimbursement::findOrFail($request->draft_id);
                if ($reimbursement->xml_path && Storage::exists($reimbursement->xml_path)) {
                    $xmlContent = Storage::get($reimbursement->xml_path);
                } else {
                    return response()->json(['error' => 'No se encontró un archivo XML en el borrador especificado.'], 422);
                }
            }

            if (!$xmlContent) {
                return response()->json(['error' => 'No se proporcionó un archivo XML válido.'], 422);
            }

            $data = $this->extractXmlData($xmlContent);

            if (empty($data['uuid'])) {
                return response()->json(['error' => 'No se encontró un UUID válido en el XML provided.'], 422);
            }

            // Ignore self if draft_id is provided
            $existingReimbursement = Reimbursement::where('uuid', $data['uuid'])
                ->when($request->draft_id, function($q) use ($request) {
                    return $q->where('id', '!=', $request->draft_id);
                })
                ->first();
            if ($existingReimbursement) {
                return response()->json([
                    'error' => 'duplicate_cfdi',
                    'error_type' => 'duplicate_cfdi',
                    'uuid' => $data['uuid'],
                    'folio' => $existingReimbursement->folio,
                    'status' => $existingReimbursement->status,
                ], 422);
            }

            $pdfFile = $request->file('pdf_file');
            
            // If PDF is missing but draft_id is provided, look for stored PDF
            if (!$pdfFile && $request->draft_id) {
                $reimbursement = Reimbursement::find($request->draft_id);
                if ($reimbursement && $reimbursement->pdf_path && Storage::exists($reimbursement->pdf_path)) {
                    $pdfFile = $reimbursement->pdf_path; // Pass as string (path) to helper
                }
            }

            $pdfValidation = $this->getValidationData($data, $pdfFile);

            return response()->json(array_merge($data, [
                'pdf_validation' => $pdfValidation,
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'forma_pago' => $data['forma_pago'] ?? null,
                'uso_cfdi' => $data['uso_cfdi'] ?? null,
                'lugar_expedicion' => $data['lugar_expedicion'] ?? null,
                'regimen_fiscal_emisor' => $data['regimen_fiscal_emisor'] ?? null,
            ]));

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
            
            // Handle both UploadedFile and existing storage paths
            $filePath = ($pdfFile instanceof \Illuminate\Http\UploadedFile) 
                ? $pdfFile->getRealPath() 
                : Storage::path($pdfFile);

            if (!file_exists($filePath)) return null;

            $pdf = $parser->parseFile($filePath);
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
        if ($fecha) {
            $fecha = date('Y-m-d', strtotime($fecha));
        }
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
            'total' => $total,
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'moneda' => $moneda,
            'tipo_comprobante' => $tipo,
            'metodo_pago' => $metodoPago,
            'forma_pago' => $formaPago,
            'uso_cfdi' => $usoCfdi,
            'lugar_expedicion' => $lugarExpedicion,
            'regimen_fiscal_emisor' => $regimenFiscalEmisor,
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
            'cost_center_id' => 'required_without:travel_event_id|nullable|exists:cost_centers,id',
            'travel_event_id' => 'required_without:cost_center_id|nullable|exists:travel_events,id',
            'week' => 'required|string',
            'category' => ['required', Rule::in($this->getCategories())], // strict validation
            'observaciones' => 'required|string',
            'xml_file' => 'nullable|file', // handled manually below
            'uuid' => 'nullable|string', // handled manually
            'total' => 'nullable|numeric', // handled manually
            'attendees_count' => 'nullable|integer|required_if:type,comida',
            'attendees_names' => 'nullable|string|required_if:type,comida',
            'location' => 'nullable|string|required_if:type,comida',
            'trip_nights' => 'nullable|integer|min:0|required_if:type,viaje',
            'trip_type' => 'nullable|in:nacional,internacional|required_if:type,viaje',
            'trip_destination' => 'nullable|string|required_if:type,viaje',
            'trip_start_date' => 'nullable|date|required_if:type,viaje',
            'trip_end_date' => 'nullable|date|after_or_equal:trip_start_date|required_if:type,viaje',
            'title' => 'nullable|string|required_if:type,viaje',
            'extra_files.*' => 'file|max:10240', // 10MB max
            'ticket_file' => 'required|file|max:10240', // Mandatory
            'parent_id' => 'nullable|exists:reimbursements,id',
            'confirm_company' => 'required',
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
        


        $draftId = $request->draft_id;
        $existingDraft = null;
        if ($draftId) {
            $existingDraft = Reimbursement::where('id', $draftId)->where('user_id', Auth::id())->where('status', 'borrador')->first();
        }
        
        $xmlPath = $request->file('xml_file') ? $request->file('xml_file')->store('xmls') : ($existingDraft ? $existingDraft->xml_path : null);
        $pdfPath = $request->file('pdf_file') ? $request->file('pdf_file')->store('pdfs') : ($existingDraft ? $existingDraft->pdf_path : null);
        $ticketPath = $request->file('ticket_file') ? $request->file('ticket_file')->store('tickets') : ($existingDraft ? $existingDraft->ticket_path : null);

        // Ownership Validation & Event Context
        $travelEvent = $request->travel_event_id ? \App\Models\TravelEvent::findOrFail($request->travel_event_id) : null;
        
        // Inherit cost center from travel event if applicable
        $effectiveCostCenterId = $travelEvent ? $travelEvent->cost_center_id : $request->cost_center_id;
        $costCenter = $effectiveCostCenterId ? CostCenter::findOrFail($effectiveCostCenterId) : null;

        // Permissions and Limits Check
        if (!$this->canCreateReimbursement($user, $request->type, $effectiveCostCenterId)) {
            return back()->withInput()->with('error', 'No tienes permiso para registrar este tipo de reembolso en este Centro de Costos o ya has alcanzado tu límite de 1 reembolso activo.');
        }

        if ($costCenter && ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector())) {
            $isAuthorized = false;
            if ($user->isDirector() && $costCenter->director_id === $user->id) $isAuthorized = true;
            if ($user->isControlObra() && $costCenter->control_obra_id === $user->id) $isAuthorized = true;
            if ($user->isExecutiveDirector() && $costCenter->director_ejecutivo_id === $user->id) $isAuthorized = true;

            if (!$isAuthorized && !$user->isAdmin()) {
                abort(403, 'No tienes permiso para registrar gastos en este centro de costos.');
            }
        }

        // DYNAMIC WORKFLOW
        $currentStepId = null;
        $initialStatus = 'pendiente';

        if ($costCenter) {
            $firstStep = $costCenter->approvalSteps()->orderBy('order', 'asc')->first();
            $initialStatus = $firstStep ? 'pendiente' : 'aprobado';
            $currentStepId = $firstStep ? $firstStep->id : null;

            // AUTO-APPROVAL
            $autoNote = "";
            $approvalData = [];
            
            while ($firstStep && $firstStep->user_id === $user->id) {
                $autoNote .= "\n[AUTO-APROBACIÓN: " . $firstStep->name . "]";
                $this->mapApprovalData($firstStep->order, $user->id, $approvalData);
                
                $nextStep = $costCenter->approvalSteps()->where('order', '>', $firstStep->order)->orderBy('order', 'asc')->first();
                if ($nextStep) {
                    $firstStep = $nextStep;
                    $currentStepId = $nextStep->id;
                } else {
                    $firstStep = null;
                    $currentStepId = null;
                    $initialStatus = 'aprobado';
                    break;
                }
            }
        }

        $finalObs = ($request->observaciones ?? "") . $autoNote;

        // Re-run validation if missing (important for drafts)
        $validationData = $request->validation_data ? json_decode($request->validation_data, true) : null;
        if (!$validationData && $request->uuid) {
            $xmlDataForVal = ['uuid' => $request->uuid, 'total' => $request->total];
            $pdfToValidate = ($request->file('pdf_file')) ? $request->file('pdf_file') : ($existingDraft ? $existingDraft->pdf_path : null);
            $validationData = $this->getValidationData($xmlDataForVal, $pdfToValidate);
        }

        // Payee Logic for single store
        $payeeId = $user->id;
        if (in_array($request->type, ['fondo_fijo', 'comida'])) {
            $payeeId = $costCenter ? ($costCenter->beneficiary_id ?? $user->id) : $user->id;
        } else {
            $payeeId = $request->input('payee_id', $user->id);
        }

        $reimbursementData = array_merge([
            'type' => $request->type,
            'cost_center_id' => $effectiveCostCenterId,
            'travel_event_id' => $request->travel_event_id,
            'week' => now()->addDays(2)->format('W-Y'),
            'category' => !empty($request->category) ? $request->category : ($existingDraft ? $existingDraft->category : 'viaticos'),
            'uuid' => !empty($request->uuid) ? $request->uuid : ($existingDraft ? $existingDraft->uuid : null),
            'rfc_emisor' => !empty($request->rfc_emisor) ? $request->rfc_emisor : ($existingDraft ? $existingDraft->rfc_emisor : null),
            'nombre_emisor' => !empty($request->nombre_emisor) ? $request->nombre_emisor : ($existingDraft ? $existingDraft->nombre_emisor : null),
            'rfc_receptor' => !empty($request->rfc_receptor) ? $request->rfc_receptor : ($existingDraft ? $existingDraft->rfc_receptor : null),
            'nombre_receptor' => !empty($request->nombre_receptor) ? $request->nombre_receptor : ($existingDraft ? $existingDraft->nombre_receptor : null),
            'folio' => !empty($request->folio) ? $request->folio : ($existingDraft ? $existingDraft->folio : null),
            'fecha' => !empty($request->fecha) ? $request->fecha : ($existingDraft ? $existingDraft->fecha : null),
            'total' => !empty($request->total) ? $request->total : ($existingDraft ? $existingDraft->total : 0),
            'subtotal' => !empty($request->subtotal) ? $request->subtotal : ($existingDraft ? $existingDraft->subtotal : 0),
            'impuestos' => !empty($request->impuestos) ? $request->impuestos : ($existingDraft ? $existingDraft->impuestos : ($request->total - $request->subtotal)),
            'moneda' => !empty($request->moneda) ? $request->moneda : ($existingDraft ? $existingDraft->moneda : 'MXN'),
            'metodo_pago' => $request->metodo_pago,
            'forma_pago' => $request->forma_pago,
            'uso_cfdi' => $request->uso_cfdi,
            'lugar_expedicion' => $request->lugar_expedicion,
            'regimen_fiscal_emisor' => $request->regimen_fiscal_emisor,
            'xml_path' => $xmlPath,
            'pdf_path' => $pdfPath,
            'ticket_path' => $ticketPath,
            'status' => $initialStatus,
            'current_step_id' => $currentStepId,
            'observaciones' => ($request->observaciones ?? "") . $autoNote,
            'attendees_count' => $request->attendees_count ?? ($existingDraft ? $existingDraft->attendees_count : 0),
            'attendees_names' => $request->attendees_names ?? ($existingDraft ? $existingDraft->attendees_names : null),
            'location' => $request->location ?? ($existingDraft ? $existingDraft->location : null),
            'trip_nights' => $request->trip_nights,
            'trip_type' => $request->trip_type,
            'trip_destination' => $request->trip_destination,
            'trip_start_date' => $request->trip_start_date,
            'trip_end_date' => $request->trip_end_date,
            'trip_status' => $request->trip_status,
            'title' => !empty($request->title) ? $request->title : ($existingDraft ? $existingDraft->title : null),
            'parent_id' => $request->parent_id,
            'company_confirmed' => $request->has('confirm_company') ? true : false,
            'validation_data' => $validationData,
            'user_id' => Auth::id(),
            'payee_id' => $payeeId,
        ], $approvalData);

        if ($existingDraft) {
            $existingDraft->update($reimbursementData);
            $reimbursement = $existingDraft;
        } else {
            $reimbursement = Reimbursement::create($reimbursementData);
        }

        // Generate Custom Folio: XXX-000000
        $prefix = strtoupper(substr($request->type, 0, 3));
        if (!str_starts_with($reimbursement->folio, $prefix . '-')) {
            $reimbursement->folio = $prefix . '-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT);
            $reimbursement->save();
        }

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
     * Show the form for editing (resuming draft).
     */
    public function edit(Reimbursement $reimbursement)
    {
        $user = Auth::user();
        if ($reimbursement->user_id !== $user->id) {
            abort(403);
        }

        if ($reimbursement->status !== 'borrador' && $reimbursement->status !== 'requiere_correccion') {
            return redirect()->route('reimbursements.show', $reimbursement);
        }

        $reimbursement->load('children');

        $type = $reimbursement->type;
        $hasInvoice = !empty($reimbursement->uuid);
        
        // Standard list of cost centers (reuse logic from create if possible)
        $costCenters = CostCenter::with('beneficiary')->orderBy('name')->get();
        // Display CURRENT processing week, not the draft's original week
        $currentWeek = now()->addDays(2)->format('W-Y');
        $categories = $this->getCategories();
        $travelEvents = \App\Models\TravelEvent::where('status', 'active')->get();

        return view('reimbursements.create', compact('reimbursement', 'type', 'hasInvoice', 'costCenters', 'currentWeek', 'categories', 'travelEvents'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Reimbursement $reimbursement)
    {
        $user = Auth::user();

        // Admin, AdminView & Owner always see
        if ($user->isAdmin() || $user->isAdminView() || $user->id === $reimbursement->user_id) {
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
        } elseif ($user->isDireccion()) {
            // Dirección sees if it reached them once
            $canSee = $reimbursement->approved_by_cxp_at !== null || !in_array($status, ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo']);
        } elseif ($user->isTreasury()) {
            // Treasury sees if it reached them once
            $canSee = $reimbursement->approved_by_direccion_at !== null || !in_array($status, ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo', 'aprobado_cxp']);
        }

        $canManage = $user->isAdmin() || $user->isAdminView() || $user->isCxp() || $user->isDireccion() || $user->isTreasury() || $user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector();

        if (!$canSee && $canManage) {
            // Allow basic technical sheet view for management roles even if not in the direct approval line
            $canSee = true;
        }

        if (!$canSee) {
            abort(403, 'Aún no tienes permiso para ver este reembolso. Está en una etapa anterior de aprobación.');
        }

        $reimbursement->load([
            'files', 
            'children', 
            'parent', 
            'costCenter.director', 
            'costCenter.controlObra', 
            'costCenter.directorEjecutivo', 
            'directorApprover', 
            'controlApprover', 
            'executiveApprover', 
            'cxpApprover', 
            'direccionApprover', 
            'treasuryApprover'
        ]);
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
                      $user->isDireccion() ||
                      ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id) ||
                      ($user->isControlObra() && $reimbursement->costCenter->control_obra_id === $user->id) ||
                       ($user->isExecutiveDirector() && $reimbursement->costCenter->director_ejecutivo_id === $user->id);

        if ($user->isAdminView()) {
            abort(403, 'Tu rol es de solo consulta y no puede realizar modificaciones.');
        }

        // Owner can update if it requires correction
        $isOwnerCorrecting = $user->id === $reimbursement->user_id && $reimbursement->status === 'requiere_correccion';

        if (!$canApprove && !$isOwnerCorrecting) {
            abort(403, 'No tienes permiso para modificar este reembolso.');
        }

        if ($isOwnerCorrecting && $request->has('is_resubmission')) {
            $request->validate([
                'pdf_file' => 'nullable|file',
                'user_correction_comment' => 'required|string',
                'attendees_count' => 'nullable|integer',
                'location' => 'nullable|string',
                'attendees_names' => 'nullable|string',
                'title' => 'nullable|string',
                'trip_destination' => 'nullable|string',
                'trip_nights' => 'nullable|integer',
                'trip_start_date' => 'nullable|date',
                'trip_end_date' => 'nullable|date',
                'nombre_emisor' => 'nullable|string',
                'fecha' => 'nullable|date',
                'subtotal' => 'nullable|numeric',
                'total' => 'nullable|numeric',
                'ticket_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,txt|max:32768',
            ]);
            
            $data = array_filter($request->only([
                'attendees_count', 'location', 'attendees_names',
                'title', 'trip_destination', 'trip_nights', 'trip_start_date', 'trip_end_date',
                'nombre_emisor', 'fecha', 'subtotal', 'total'
            ]), function($value) { return !is_null($value); });

            // Safety: If reimbursement has a UUID, we MUST NOT allow changing core fiscal fields manually
            if (!empty($reimbursement->uuid)) {
                unset($data['nombre_emisor'], $data['fecha'], $data['subtotal'], $data['total']);
            }

            if ($request->hasFile('ticket_file')) {
                if ($reimbursement->ticket_path) Storage::delete($reimbursement->ticket_path);
                $data['ticket_path'] = $request->file('ticket_file')->store('tickets');
            }

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
            if ($reimbursement->approved_by_direccion_id !== null) {
                $data['status'] = 'aprobado_direccion'; // Back to Treasury
            } elseif ($reimbursement->approved_by_cxp_id !== null) {
                $data['status'] = 'aprobado_cxp'; // Back to Dirección
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
                // DYNAMIC APPROVAL LOGIC
                $currentStep = $reimbursement->currentStep;
                if (($currentStep && $user->id === $currentStep->user_id) || $user->isAdmin()) {
                    // Record approval in legacy columns if possible (order 1-6)
                    $this->mapApprovalData($currentStep->order ?? 1, $user->id, $data);
                    
                    // Find next step
                    $nextStep = $reimbursement->costCenter->approvalSteps()
                        ->where('order', '>', $currentStep->order ?? 0)
                        ->orderBy('order', 'asc')
                        ->first();
                        
                    if ($nextStep) {
                        $data['current_step_id'] = $nextStep->id;
                        $data['status'] = 'pendiente'; // Or something better, but status names are tied to steps in current views
                    } else {
                        $data['current_step_id'] = null;
                        $data['status'] = 'aprobado';
                    }
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
                foreach($cxpUsers as $cxp) $cxp->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de Subdirección.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Dir. Ejecutivo, pasa a Subdirección.", "info"));
            } elseif ($data['status'] === 'aprobado_cxp') {
                $direccionUsers = User::where('role', 'direccion')->get();
                foreach($direccionUsers as $direccion) $direccion->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: revisado por Subdirección, pendiente de aprobación de Dirección.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} revisado por Subdirección, pasa a Dirección.", "info"));
            } elseif ($data['status'] === 'aprobado_direccion') {
                $treasuryUsers = User::where('role', 'tesoreria')->get();
                foreach($treasuryUsers as $treasury) $treasury->notify(new ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: aprobado por Dirección, pendiente de pago.", "warning"));
                if ($owner) $owner->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Dirección, pasa a Cuentas por Pagar.", "info"));
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
            'pdf_file' => 'required|file',
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
        if (Auth::user()->isAdminView()) {
            abort(403, 'Tu rol es de solo consulta.');
        }

        if ($reimbursement->xml_path) Storage::delete($reimbursement->xml_path);
        if ($reimbursement->pdf_path) Storage::delete($reimbursement->pdf_path);
        
        $reimbursement->delete();

        return redirect()->back()
                         ->with('success', 'Borrador eliminado.');
    }

    /**
     * Export reimbursements to CSV for Excel.
     */
    public function export(Request $request)
    {
        set_time_limit(300);
        $user = Auth::user();
        $query = Reimbursement::with(['user', 'costCenter', 'directorApprover', 'controlApprover', 'executiveApprover', 'cxpApprover', 'direccionApprover', 'treasuryApprover']);

        // Mandatory date filtering for export as per request (Creation or Expedition)
        if ($request->filled('from_date') || $request->filled('to_date')) {
            $fromDate = $request->from_date;
            $toDate = $request->to_date;

            $query->where(function($q) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $q->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                      ->orWhereBetween('fecha', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $q->whereDate('created_at', '>=', $fromDate)
                      ->orWhereDate('fecha', '>=', $fromDate);
                } elseif ($toDate) {
                    $q->whereDate('created_at', '<=', $toDate)
                      ->orWhereDate('fecha', '<=', $toDate);
                }
            });
        }

        // Apply common filters
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

        // Apply role-based visibility
        if (!$user->isAdmin() && !$user->isAdminView()) {
            if ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
                 $query->whereHas('costCenter', function($q) use ($user) {
                    if ($user->isDirector()) $q->where('director_id', $user->id);
                    if ($user->isControlObra()) $q->where('control_obra_id', $user->id);
                    if ($user->isExecutiveDirector()) $q->where('director_ejecutivo_id', $user->id);
                });
            } elseif ($user->isCxp() || $user->isDireccion() || $user->isTreasury()) {
                // CXP and Treasury see all approved up to their level or beyond
            } else {
                $query->where('user_id', $user->id);
            }
        }

        $reimbursements = $query->latest()->get();

        $fileName = 'reembolsos_export_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        return response()->streamDownload(function () use ($reimbursements) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Folio',
                'UUID',
                'Emisor',
                'Tipo de Solicitud',
                'Centro de Costos',
                'Categoría',
                'Semana',
                'Receptor',
                'Fecha Expedición XML',
                'Fecha Creación Reembolso',
                'Método de Pago',
                'Forma de Pago',
                'Uso CFDI',
                'CP Expedición',
                'Régimen Fiscal Emisor',
                'Total',
                'Estatus',
                'Aprobaciones',
                'Validación XML vs PDF (UUID)',
                'Validación XML vs PDF (Monto)'
            ]);

            foreach ($reimbursements as $r) {
                // Formatting Aprobaciones
                $approvals = [];
                
                // Director
                if ($r->approved_by_director_at) {
                    $approvals[] = "Director: " . ($r->directorApprover->name ?? 'N/A') . " (" . $r->approved_by_director_at->format('d/m/Y H:i') . ")";
                } else {
                    $approvals[] = "Director: Pendiente";
                }

                // Control
                if ($r->approved_by_control_at) {
                    $approvals[] = "Control de Obra: " . ($r->controlApprover->name ?? 'N/A') . " (" . $r->approved_by_control_at->format('d/m/Y H:i') . ")";
                } else {
                    $approvals[] = "Control de Obra: Pendiente";
                }

                // Executive
                if ($r->approved_by_executive_at) {
                    $approvals[] = "Dir. Ejecutivo: " . ($r->executiveApprover->name ?? 'N/A') . " (" . $r->approved_by_executive_at->format('d/m/Y H:i') . ")";
                } else {
                    $approvals[] = "Dir. Ejecutivo: Pendiente";
                }

                // Subdirección
                if ($r->approved_by_cxp_at) {
                    $approvals[] = "Subdirección: " . ($r->cxpApprover->name ?? 'N/A') . " (" . $r->approved_by_cxp_at->format('d/m/Y H:i') . ")";
                }

                // Dirección
                if ($r->approved_by_direccion_at) {
                    $approvals[] = "Dirección: " . ($r->direccionApprover->name ?? 'N/A') . " (" . $r->approved_by_direccion_at->format('d/m/Y H:i') . ")";
                }

                // Cuentas por Pagar
                if ($r->approved_by_treasury_at) {
                    $approvals[] = "Cuentas por Pagar: " . ($r->treasuryApprover->name ?? 'N/A') . " (" . $r->approved_by_treasury_at->format('d/m/Y H:i') . ")";
                }

                $approvalsStr = implode(" | ", $approvals);

                // Validation info
                $val = $r->validation_data;
                $uuidMatch = "N/A";
                $totalMatch = "N/A";
                
                if ($val) {
                    if (isset($val['uuid_match'])) $uuidMatch = $val['uuid_match'] ? "Coincide" : "No Coincide";
                    if (isset($val['total_match'])) $totalMatch = $val['total_match'] ? "Coincide" : "No Coincide";
                }

                fputcsv($file, [
                    $r->folio ?? 'N/A',
                    $r->uuid ?? 'N/A',
                    $r->nombre_emisor . " (" . $r->rfc_emisor . ")",
                    ucfirst(str_replace('_', ' ', $r->type ?? 'Reembolso')),
                    $r->costCenter->name ?? 'N/A',
                    ucfirst($r->category ?? 'N/A'),
                    $r->week ?? 'N/A',
                    $r->nombre_receptor . " (" . $r->rfc_receptor . ")",
                    $r->fecha ? $r->fecha->format('d/m/Y H:i') : 'N/A',
                    $r->created_at ? $r->created_at->format('d/m/Y H:i') : 'N/A',
                    $r->metodo_pago ?? 'N/A',
                    $r->forma_pago ?? 'N/A',
                    $r->uso_cfdi ?? 'N/A',
                    $r->lugar_expedicion ?? 'N/A',
                    $r->regimen_fiscal_emisor ?? 'N/A',
                    "$ " . number_format((float)$r->total, 2) . " " . ($r->moneda ?? 'MXN'),
                    ucwords(str_replace('_', ' ', $r->status)),
                    $approvalsStr,
                    $uuidMatch,
                    $totalMatch
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }

    /**
     * Massive approval action from the audit selected view.
     */
    public function bulkAuditAction(Request $request)
    {
        Log::info("BULK_AUDIT_ACTION_START: User=" . Auth::id() . " Action=" . $request->action . " IDs=" . json_encode($request->ids));

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:reimbursements,id',
            'action' => 'required|in:aprobado,rechazado',
            'password' => 'required|string',
            'rejection_reason' => 'nullable|string|required_if:action,rechazado',
        ]);

        $user = Auth::user();

        // Check password
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Contraseña incorrecta.');
        }

        $processed = 0;
        $failed = 0;

        foreach ($request->ids as $id) {
            $reimbursement = Reimbursement::find($id);
            if (!$reimbursement) continue;

            // Authorization check (same logic as single update)
            $canApprove = $user->isAdmin() || 
                          $user->isTreasury() || 
                          $user->isCxp() || 
                          $user->isDireccion() ||
                          ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id) ||
                          ($user->isControlObra() && $reimbursement->costCenter->control_obra_id === $user->id) ||
                          ($user->isExecutiveDirector() && $reimbursement->costCenter->director_ejecutivo_id === $user->id);

            if (!$canApprove) {
                $failed++;
                continue;
            }

            $data = [];

            if ($request->action === 'rechazado') {
                 $currentObs = $reimbursement->observaciones;
                 $newObs = "[MASIVO] RECHAZADO por " . $user->name . " (Rechazo Masivo) el " . now()->format('d/m/Y H:i') . ": " . $request->rejection_reason;
                 $data['observaciones'] = $currentObs ? ($currentObs . "\n" . $newObs) : $newObs;
                 $data['status'] = 'rechazado';
            } else {
                 $currentObs = $reimbursement->observaciones;
                 $newObs = "[MASIVO] APROBADO masivamente por " . $user->name . " el " . now()->format('d/m/Y H:i');
                 $data['observaciones'] = $currentObs ? ($currentObs . "\n" . $newObs) : $newObs;
                 // Action === 'aprobado'
                 // Legacy state mapping
                if ($user->isDirector() && $reimbursement->costCenter->director_id === $user->id) {
                     $data['approved_by_director_id'] = $user->id;
                     $data['approved_by_director_at'] = now();
                     $data['status'] = 'aprobado_director';
                } elseif ($user->isControlObra() && $reimbursement->costCenter->control_obra_id === $user->id) {
                     $data['approved_by_control_id'] = $user->id;
                     $data['approved_by_control_at'] = now();
                     $data['status'] = 'aprobado_control';
                } elseif ($user->isExecutiveDirector() && $reimbursement->costCenter->director_ejecutivo_id === $user->id) {
                     $data['approved_by_executive_id'] = $user->id;
                     $data['approved_by_executive_at'] = now();
                     $data['status'] = 'aprobado_ejecutivo';
                } elseif ($user->isCxp()) {
                     $data['approved_by_cxp_id'] = $user->id;
                     $data['approved_by_cxp_at'] = now();
                     $data['status'] = 'aprobado_cxp';
                } elseif ($user->isDireccion()) {
                     $data['approved_by_direccion_id'] = $user->id;
                     $data['approved_by_direccion_at'] = now();
                     $data['status'] = 'aprobado_direccion';
                } elseif ($user->isTreasury() || $user->isAdmin()) {
                     $data['approved_by_treasury_id'] = $user->id;
                     $data['approved_by_treasury_at'] = now();
                     $data['status'] = 'aprobado';
                }

                // New logic (Dynamic Steps) if it exists
                $currentStep = $reimbursement->currentStep;
                if (($currentStep && $user->id === $currentStep->user_id) || $user->isAdmin()) {
                    $this->mapApprovalData($currentStep->order ?? 1, $user->id, $data);
                    
                    $nextStep = $reimbursement->costCenter->approvalSteps()
                        ->where('order', '>', $currentStep->order ?? 0)
                        ->orderBy('order', 'asc')
                        ->first();
                        
                    if ($nextStep) {
                        $data['current_step_id'] = $nextStep->id;
                        $data['status'] = 'pendiente';
                    } else {
                        $data['current_step_id'] = null;
                        $data['status'] = 'aprobado';
                    }
                }
            }

            $reimbursement->update($data);
            $processed++;

            // Notifications
            if (isset($data['status'])) {
                $owner = $reimbursement->user;
                if ($data['status'] === 'aprobado_director') {
                    $target = $reimbursement->costCenter->controlObra;
                    if ($target) $target->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de Control de Obra.", "warning"));
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Director, pasa a Control de Obra.", "info"));
                } elseif ($data['status'] === 'aprobado_control') {
                    $target = $reimbursement->costCenter->directorEjecutivo;
                    if ($target) $target->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de Director Ejecutivo.", "warning"));
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Control de Obra, pasa a Dir. Ejecutivo.", "info"));
                } elseif ($data['status'] === 'aprobado_ejecutivo') {
                    $cxpUsers = \App\Models\User::where('role', 'accountant')->get();
                    foreach($cxpUsers as $cxp) $cxp->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: pendiente de revisión de Subdirección.", "warning"));
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Dir. Ejecutivo, pasa a Subdirección.", "info"));
                } elseif ($data['status'] === 'aprobado_cxp') {
                    $direccionUsers = \App\Models\User::where('role', 'direccion')->get();
                    foreach($direccionUsers as $direccion) $direccion->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: revisado por Subdirección, pendiente de aprobación de Dirección.", "warning"));
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} revisado por Subdirección, pasa a Dirección.", "info"));
                } elseif ($data['status'] === 'aprobado_direccion') {
                    $treasuryUsers = \App\Models\User::where('role', 'tesoreria')->get();
                    foreach($treasuryUsers as $treasury) $treasury->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Reembolso {$reimbursement->folio}: aprobado por Dirección, pendiente de pago.", "warning"));
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado por Dirección, pasa a Cuentas por Pagar.", "info"));
                } elseif ($data['status'] === 'aprobado') {
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} aprobado finalmente.", "success"));
                } elseif ($data['status'] === 'rechazado') {
                    if ($owner) $owner->notify(new \App\Notifications\ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} RECHAZADO.", "danger"));
                }
            }
        }

        Log::info("BULK_AUDIT_ACTION_END: Processed={$processed} Failed={$failed}");
        
        $msg = "Se procesaron $processed trámites con éxito.";
        if ($failed > 0) {
            $msg .= " No se pudieron procesar $failed trámites por falta de permisos o estado inválido.";
        }
        
        return back()->with('success', $msg);
    }

    /**
     * Bulk approve reimbursements via CSV upload.
     */
    public function bulkApprove(Request $request)
    {
        set_time_limit(300); // 5 minutes
        \Illuminate\Support\Facades\DB::disableQueryLog();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTreasury() && !$user->isCxp()) {
            abort(403, 'No tienes permisos para realizar aprobaciones masivas.');
        }

        $request->validate([
            'csv_file' => 'required|file',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'El archivo CSV está vacío o no se puede leer.');
        }

        // Find Folio and UUID columns dynamically
        $folioIdx = array_search('Folio', $header);
        $uuidIdx = array_search('UUID', $header);

        if ($folioIdx === false || $uuidIdx === false) {
            fclose($handle);
            return back()->with('error', 'El archivo CSV no tiene el formato correcto (faltan las columnas Folio o UUID).');
        }

        $processed = 0;
        $failed = 0;
        $errors = [
            'not_found' => [],
            'already_approved' => [],
            'invalid_status' => [],
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $folio = $row[$folioIdx] ?? null;
            $uuid = $row[$uuidIdx] ?? null;

            if (!$folio || !$uuid || $uuid === 'N/A') continue;

            $reimbursement = Reimbursement::where('folio', $folio)
                ->where('uuid', $uuid)
                ->first();

            if ($reimbursement) {
                // 1. Check if already approved
                if ($reimbursement->status === 'aprobado') {
                    $failed++;
                    $errors['already_approved'][] = "Folio $folio: Ya se encontraba aprobado.";
                    continue;
                }

                // 2. Check if rejected
                if ($reimbursement->status === 'rechazado') {
                    $failed++;
                    $errors['invalid_status'][] = "Folio $folio: Está marcado como rechazado.";
                    continue;
                }

                // 3. Check workflow profile
                // Admins, CXP, and Treasury can approve records that are in the administrative pipeline
                $adminFlow = ['aprobado_ejecutivo', 'aprobado_cxp', 'aprobado_direccion'];
                if (!$user->isAdmin() && !in_array($reimbursement->status, $adminFlow)) {
                    $failed++;
                    $statusLabel = ucwords(str_replace('_', ' ', $reimbursement->status));
                    $errors['invalid_status'][] = "Folio $folio: El estatus '$statusLabel' requiere aprobaciones previas de Directores antes de pasar a Cuentas por Pagar.";
                    continue;
                }

                // If we reach here, we can approve
                $reimbursement->update([
                    'status' => 'aprobado',
                    'approved_by_treasury_id' => $user->id,
                    'approved_by_treasury_at' => now(),
                ]);
                $processed++;

                // Notify owner - wrapped in try catch to avoid stopping the whole process
                try {
                    if ($reimbursement->user) {
                        $reimbursement->user->notify(new ReimbursementNotification($reimbursement, "Tu reembolso {$reimbursement->folio} fue aprobado masivamente.", "success"));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error notifying user in bulk approval: " . $e->getMessage());
                }
            } else {
                $failed++;
                $errors['not_found'][] = "Folio $folio (UUID: $uuid): No encontrado en el sistema.";
            }
        }

        fclose($handle);

        $msg = "Se procesaron $processed aprobaciones exitosamente.";
        if ($failed > 0) {
            $msg .= " No se pudieron procesar $failed registros.";
            return back()->with('warning', $msg)->with('bulk_errors_categorized', $errors);
        }

        return back()->with('success', $msg);
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
    /**
     * Download a file (XML or PDF/Image) with a clean name.
     */
    public function downloadFile(Reimbursement $reimbursement, $type)
    {
        if ($type === 'xml') {
            if (!$reimbursement->xml_path || !Storage::exists($reimbursement->xml_path)) {
                abort(404, 'Archivo XML no encontrado.');
            }
            return Storage::download($reimbursement->xml_path, 'factura-' . ($reimbursement->folio ?? 'archivo') . '.xml');
        } elseif ($type === 'pdf') {
            if (!$reimbursement->pdf_path || !Storage::exists($reimbursement->pdf_path)) {
                abort(404, 'Archivo no encontrado.');
            }
            
            $extension = pathinfo($reimbursement->pdf_path, PATHINFO_EXTENSION);
            if ($extension === 'bin') {
                $mime = Storage::mimeType($reimbursement->pdf_path);
                if (str_contains($mime, 'pdf')) $extension = 'pdf';
                elseif (str_contains($mime, 'image/jpeg')) $extension = 'jpg';
                elseif (str_contains($mime, 'image/png')) $extension = 'png';
                else $extension = 'file';
            }

            $cleanName = 'comprobante-' . ($reimbursement->folio ?? 'gasto') . '.' . $extension;
            return Storage::download($reimbursement->pdf_path, $cleanName);
        } elseif ($type === 'ticket') {
            if (!$reimbursement->ticket_path || !Storage::exists($reimbursement->ticket_path)) {
                abort(404, 'Ticket / Prueba no encontrado.');
            }
            
            $extension = pathinfo($reimbursement->ticket_path, PATHINFO_EXTENSION);
            $cleanName = 'ticket-' . ($reimbursement->folio ?? 'prueba') . '.' . $extension;
            return Storage::download($reimbursement->ticket_path, $cleanName);
        }
        
        abort(404);
    }

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
                abort(404, 'Archivo no encontrado.');
            }
            
            $path = Storage::path($reimbursement->pdf_path);
            $mimeType = Storage::mimeType($reimbursement->pdf_path);
            
            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($reimbursement->pdf_path) . '"'
            ]);
        } elseif ($type === 'ticket') {
            if (!$reimbursement->ticket_path || !Storage::exists($reimbursement->ticket_path)) {
                abort(404, 'Ticket / Prueba no encontrado.');
            }
            
            $path = Storage::path($reimbursement->ticket_path);
            $mimeType = Storage::mimeType($reimbursement->ticket_path);
            
            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($reimbursement->ticket_path) . '"'
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

    /**
     * Maps approval step order to legacy columns for backward compatibility.
     */
    private function mapApprovalData($order, $userId, &$data)
    {
        $map = [
            1 => ['approved_by_director_id', 'approved_by_director_at'],
            2 => ['approved_by_control_id', 'approved_by_control_at'],
            3 => ['approved_by_executive_id', 'approved_by_executive_at'],
            4 => ['approved_by_cxp_id', 'approved_by_cxp_at'],
            5 => ['approved_by_direccion_id', 'approved_by_direccion_at'],
            6 => ['approved_by_treasury_id', 'approved_by_treasury_at'],
        ];

        if (isset($map[$order])) {
            $data[$map[$order][0]] = $userId;
            $data[$map[$order][1]] = now();
        }
    }

    /**
     * Auto-save or Manual Draft Save.
     */
    public function autoStore(Request $request)
    {
        set_time_limit(180); // Higher limit for file uploads in drafts
        
        try {
            $user = Auth::user();
            $items = $request->input('items', []);
            $draftIds = [];

            // If it's a 'viaje' type, it's a single record form
            if ($request->input('type') === 'viaje') {
                $items = [ $request->all() ]; // Wrap as a single item for uniform processing
                if ($request->input('draft_id')) {
                    $items[0]['draft_id'] = $request->input('draft_id');
                }
            }

            $travelEventId = $request->input('travel_event_id');
            $travelEvent = $travelEventId ? \App\Models\TravelEvent::find($travelEventId) : null;
            $requestCostCenterId = $travelEvent ? $travelEvent->cost_center_id : $request->input('cost_center_id');

            foreach ($items as $index => $itemData) {
                try {
                    $id = $itemData['draft_id'] ?? null;
                    
                    $payeeId = $request->input('payee_id');

                    $data = [
                        'type' => $request->input('type'),
                        'cost_center_id' => $requestCostCenterId,
                        'travel_event_id' => $travelEventId,
                        'week' => $request->input('week'),
                        'title' => $request->input('title') ?: ($travelEvent ? $travelEvent->name : ($itemData['nombre_emisor'] ?? 'Sin Título')),
                        'user_id' => $user->id,
                        'payee_id' => $payeeId,
                        'status' => 'borrador',
                    ];

                    // Merge specific fields from the item
                    $fields = [
                        'nombre_emisor', 'fecha', 'total', 'subtotal', 'impuestos', 'moneda',
                        'rfc_emisor', 'rfc_receptor', 'nombre_receptor', 'observaciones',
                        'metodo_pago', 'forma_pago', 'uso_cfdi', 'lugar_expedicion', 'regimen_fiscal_emisor',
                        'trip_destination', 'trip_nights', 'trip_start_date', 'trip_end_date', 'location',
                        'uuid', 'folio', 'category', 'trip_type'
                    ];
                    
                    // Fallback from Travel Event for inheritance
                    if ($travelEvent) {
                        $data['location'] = $travelEvent->location;
                        $data['trip_type'] = $travelEvent->trip_type;
                        $data['trip_start_date'] = $travelEvent->start_date;
                        $data['trip_end_date'] = $travelEvent->end_date;
                    }

                    foreach ($fields as $field) {
                        // PREVENT DATA LOSS: Only update if the field in the request is NOT empty
                        // This prevents a cleared frontend state from wiping valid DB data
                        if (isset($itemData[$field]) && $itemData[$field] !== "" && $itemData[$field] !== "null" && $itemData[$field] !== null) {
                            $data[$field] = $itemData[$field];
                        }
                    }

                    // Enhanced and Safer File Handling (10MB limit)
                    $maxSize = 10 * 1024 * 1024;
                    if ($request->hasFile("items.{$index}.xml_file") && $request->file("items.{$index}.xml_file")->isValid()) {
                        if ($request->file("items.{$index}.xml_file")->getSize() <= $maxSize) {
                            $data['xml_path'] = $request->file("items.{$index}.xml_file")->store('reimbursements/xmls/drafts');
                        }
                    }
                    if ($request->hasFile("items.{$index}.pdf_file") && $request->file("items.{$index}.pdf_file")->isValid()) {
                        if ($request->file("items.{$index}.pdf_file")->getSize() <= $maxSize) {
                            $data['pdf_path'] = $request->file("items.{$index}.pdf_file")->store('reimbursements/pdfs/drafts');
                        }
                    }
                    if ($request->hasFile("items.{$index}.ticket_file") && $request->file("items.{$index}.ticket_file")->isValid()) {
                        if ($request->file("items.{$index}.ticket_file")->getSize() <= $maxSize) {
                            $data['ticket_path'] = $request->file("items.{$index}.ticket_file")->store('reimbursements/tickets/drafts');
                        }
                    }

                    // Support for 'viaje' type where files are at top level
                    if ($request->input('type') === 'viaje') {
                        if ($request->hasFile('xml_file') && $request->file('xml_file')->isValid()) {
                            $data['xml_path'] = $request->file('xml_file')->store('reimbursements/xmls/drafts');
                        }
                        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                            $data['pdf_path'] = $request->file('pdf_file')->store('reimbursements/pdfs/drafts');
                        }
                        if ($request->hasFile('ticket_file') && $request->file('ticket_file')->isValid()) {
                            $data['ticket_path'] = $request->file('ticket_file')->store('reimbursements/tickets/drafts');
                        }
                    }

                    // FIND EXISTING: Prevent duplicate records by matching ID or UUID for the same user
                    $reimbursement = null;
                    if ($id) {
                        $reimbursement = Reimbursement::where('user_id', $user->id)->find($id);
                    } 
                    
                    if (!$reimbursement && isset($data['uuid'])) {
                        $reimbursement = Reimbursement::where('user_id', $user->id)
                                                    ->where('uuid', $data['uuid'])
                                                    ->where('status', 'borrador')
                                                    ->first();
                    }

                    if ($reimbursement) {
                        $reimbursement->update($data);
                    } else {
                        $reimbursement = Reimbursement::create($data);
                    }

                    if (!$reimbursement->folio) {
                        $prefix = strtoupper(substr($reimbursement->type, 0, 3)) ?: 'REE';
                        $reimbursement->folio = 'DRAFT-' . $prefix . '-' . str_pad($reimbursement->id, 5, '0', STR_PAD_LEFT);
                        $reimbursement->save();
                    }

                    $draftIds[$index] = [
                        'id' => $reimbursement->id,
                        'has_xml' => !empty($reimbursement->xml_path),
                        'has_pdf' => !empty($reimbursement->pdf_path),
                        'has_ticket' => !empty($reimbursement->ticket_path),
                        'folio' => $reimbursement->folio
                    ];
                    Log::info("Draft saved for item {$index}: ID {$reimbursement->id}");

                } catch (\Exception $e) {
                    Log::error("Failed to save draft item {$index}: " . $e->getMessage());
                    // For AJAX drafts, we continue the loop but log the error
                }
            }

            return response()->json([
                'success' => true, 
                'ids' => $draftIds,
                'main_id' => !empty($draftIds) ? $draftIds[0]['id'] : null
            ]);
        } catch (\Exception $e) {
            Log::error('Draft Save Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function canCreateReimbursement(\App\Models\User $user, $type, $costCenterId)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $cc = \App\Models\CostCenter::find($costCenterId);
        if (!$cc) return false;

        $authorizedUser = $cc->authorizedUsers()->where('users.id', $user->id)->first();
        if (!$authorizedUser) return false;

        $isMarked = $authorizedUser->pivot->can_do_special;

        // Special types (Fondo Fijo, Comida, Viaje) are restricted to marked users
        if (in_array($type, ['fondo_fijo', 'comida', 'viaje'])) {
            return (bool)$isMarked;
        }

        // Standard reimbursement: unmarked users can only have ONE (none pending/approved)
        if (!$isMarked) {
            $hasExisting = Reimbursement::where('cost_center_id', $cc->id)
                ->where('user_id', $user->id)
                ->where('status', '!=', 'rechazado')
                ->exists();
            return !$hasExisting;
        }

        return true;
    }

    /**
     * Helper to apply tab-specific scoping to a query.
     */
    private function applyTabScope($query, $tab, $user)
    {
        if ($tab === 'active') {
            // Strictly Personal: Pending
            $query->where('user_id', $user->id)
                  ->whereNotIn('status', ['aprobado', 'rechazado']);

        } elseif ($tab === 'history') {
            // Strictly Personal: Finished
            $query->where('user_id', $user->id)
                  ->whereIn('status', ['aprobado', 'rechazado']);

        } elseif ($tab === 'management') {
            // Approvals & Oversight for designated roles
            if ($user->isAdmin() || $user->isAdminView()) {
                $query->whereNotIn('status', ['aprobado', 'rechazado', 'en_evento']);
            } else {
                // DYNAMIC VISIBILITY: User sees it if they are the assigned approver
                $query->whereHas('currentStep', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

        } elseif ($tab === 'global_history') {
            // History for elevated roles or personal history
            if ($user->isAdmin() || $user->isAdminView() || $user->isTreasury() || $user->isCxp() || $user->isDireccion()) {
                $query->whereIn('status', ['aprobado', 'rechazado']);
            } elseif ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
                $query->whereHas('costCenter', function($q) use ($user) {
                    if ($user->isDirector()) $q->where('director_id', $user->id);
                    if ($user->isControlObra()) $q->where('control_obra_id', $user->id);
                    if ($user->isExecutiveDirector()) $q->where('director_ejecutivo_id', $user->id);
                })->whereIn('status', ['aprobado', 'rechazado']);
            } else {
                $query->where('user_id', $user->id)
                      ->whereIn('status', ['aprobado', 'rechazado']);
            }
        } else {
            // DEFAULT FALLBACK: Personal Scope (mostly for weekly_summary if needed)
            $query->where('user_id', $user->id);
        }
    }
}
