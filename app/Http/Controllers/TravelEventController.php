<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\TravelEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\NotificationBatchService;

class TravelEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $travelEvents = TravelEvent::with(['user', 'director', 'costCenter'])->latest()->paginate(10);
        return view('travel_events.index', compact('travelEvents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $directors = User::whereIn('role', ['admin', 'director'])->get();
        $costCenters = CostCenter::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        
        // Generate suggested code: TRV-YYYY-XXXX
        $year = date('Y');
        $count = TravelEvent::whereYear('created_at', $year)->count() + 1;
        $suggestedCode = "TRV-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        return view('travel_events.create', compact('directors', 'costCenters', 'users', 'suggestedCode'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cost_center_id' => 'required|exists:cost_centers,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:travel_events,code|max:50',
            'director_id' => 'required|exists:users,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'trip_type' => 'required|in:nacional,internacional',
            'approval_evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $evidencePath = null;
        if ($request->hasFile('approval_evidence')) {
            $evidencePath = $request->file('approval_evidence')->store('evidence', 'public');
        }

        $travelEvent = TravelEvent::create([
            'cost_center_id' => $request->cost_center_id,
            'name' => $request->name,
            'code' => $request->code,
            'user_id' => Auth::id(),
            'director_id' => $request->director_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'description' => $request->description,
            'status' => 'active',
            'approval_evidence_path' => $evidencePath,
            'trip_type' => $request->trip_type,
        ]);

        $travelEvent->participants()->sync($request->user_ids);

        return redirect()->route('travel_events.index')->with('success', 'Viaje o Evento creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, TravelEvent $travelEvent)
    {
        if (!$request->filled('period_type')) {
            $request->merge([
                'period_type' => 'month',
                'period_month' => now()->format('Y-m')
            ]);
        }

        $periods = \App\Models\Reimbursement::getAvailableTimePeriods();
        $travelEvent->load(['user', 'director', 'costCenter', 'participants']);
        
        $reimbursements = $travelEvent->reimbursements()
            ->applyTimeFilters($request)
            ->with(['user', 'currentStep'])
            ->latest()
            ->get();
            
        $stats = [
            'total_amount' => $reimbursements->sum('total'),
            'count' => $reimbursements->count(),
            'approved_amount' => $reimbursements->where('status', 'aprobado')->sum('total'),
            'pending_amount' => $reimbursements->whereNotIn('status', ['aprobado', 'rechazado', 'borrador', 'en_evento'])->sum('total'),
            'queued_amount' => $reimbursements->where('status', 'en_evento')->sum('total'),
        ];

        return view('travel_events.show', compact('travelEvent', 'reimbursements', 'stats', 'periods'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TravelEvent $travelEvent)
    {
        $directors = User::whereIn('role', ['admin', 'director'])->get();
        $costCenters = CostCenter::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('travel_events.edit', compact('travelEvent', 'directors', 'costCenters', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelEvent $travelEvent)
    {
        $request->validate([
            'cost_center_id' => 'required|exists:cost_centers,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:travel_events,code,' . $travelEvent->id,
            'director_id' => 'required|exists:users,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,cancelled',
            'trip_type' => 'required|in:nacional,internacional',
            'approval_evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = $request->except(['approval_evidence', 'user_ids']);
        if ($request->hasFile('approval_evidence')) {
            if ($travelEvent->approval_evidence_path) {
                Storage::disk('public')->delete($travelEvent->approval_evidence_path);
            }
            $data['approval_evidence_path'] = $request->file('approval_evidence')->store('evidence', 'public');
        }

        $travelEvent->update($data);
        $travelEvent->participants()->sync($request->user_ids);

        return redirect()->route('travel_events.index')->with('success', 'Viaje o Evento actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelEvent $travelEvent)
    {
        $travelEvent->delete();
        return redirect()->route('travel_events.index')->with('success', 'Viaje o Evento eliminado.');
    }

    /**
     * Cerrar el evento y liberar los reembolsos retenidos a la semana en curso.
     */
    public function closeEvent(TravelEvent $travelEvent)
    {
        // Solo el director del evento o admin
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion') && Auth::id() !== $travelEvent->director_id) {
            abort(403, 'No tienes permiso para cerrar este evento.');
        }

        if ($travelEvent->status !== 'active') {
            return redirect()->back()->with('error', 'El evento ya está cerrado o cancelado.');
        }

        // Obtener el paso inicial del Centro de Costos correspondiente al evento
        $costCenter = $travelEvent->costCenter;
        $firstStep = $costCenter ? $costCenter->approvalSteps()->orderBy('order', 'asc')->first() : null;
        
        $initialStatus = $firstStep ? 'pendiente' : 'aprobado';
        $currentStepId = $firstStep ? $firstStep->id : null;
        
        // Semana de facturación actual
        $currentWeek = now()->addDays(2)->format('W-Y');

        // Buscar todos los reembolsos vinculados en estado 'en_evento'
        $reimbursements = \App\Models\Reimbursement::where('travel_event_id', $travelEvent->id)
            ->where('status', 'en_evento')
            ->get();

        $count = 0;
        foreach ($reimbursements as $reimb) {
            // Evaluamos si el creador es el mismo aprobador para un posible auto-avance inmediato
            $loopStep = $firstStep;
            $loopStatus = $initialStatus;
            $loopStepId = $currentStepId;
            $creatorId = $reimb->user_id;

            // Herencia de datos del Evento/Viaje
            $inheritanceData = [
                'cost_center_id' => $travelEvent->cost_center_id,
                'location' => $travelEvent->location,
                'trip_type' => $travelEvent->trip_type,
                'trip_start_date' => $travelEvent->start_date,
                'trip_end_date' => $travelEvent->end_date,
            ];

            // Auto-Approval Logic heredado de bulkStore
            while ($loopStep && $loopStep->user_id === $creatorId) {
                // Registrar aprobación en los campos correspondientes (Simulando lo que hace mapApprovalData en el otro controlador)
                // Usamos el orden de aprobación definido en el sistema (N1=Director, N2=Control, etc.)
                if ($loopStep->order == 1) { $inheritanceData['approved_by_director_id'] = $creatorId; $inheritanceData['approved_by_director_at'] = now(); }
                if ($loopStep->order == 2) { $inheritanceData['approved_by_control_id'] = $creatorId; $inheritanceData['approved_by_control_at'] = now(); }
                if ($loopStep->order == 3) { $inheritanceData['approved_by_executive_id'] = $creatorId; $inheritanceData['approved_by_executive_at'] = now(); }
                
                $nextStep = $costCenter->approvalSteps()->where('order', '>', $loopStep->order)->orderBy('order', 'asc')->first();
                if ($nextStep) {
                    $loopStep = $nextStep;
                    $loopStepId = $nextStep->id;
                } else {
                    $loopStep = null;
                    $loopStepId = null;
                    $loopStatus = 'aprobado';
                    break;
                }
            }

            $reimb->update(array_merge([
                'status' => $loopStatus,
                'current_step_id' => $loopStepId,
                'week' => $currentWeek
            ], $inheritanceData));

            $count++;
        }

        $travelEvent->update(['status' => 'completed']);

        // Notificar al siguiente en línea (N1, usualmente el Director de Centro de Costos) si hay facturas procesadas
        if ($count > 0 && $costCenter && $firstStep) {
            $notifMsg = "El Viaje '{$travelEvent->name}' ha sido CERRADO enviando {$count} facturas al ciclo N1 de aprobación.";
            // Notification removed to favor consolidated batching 5 minutes later
            
            foreach ($reimbursements as $reimb) {
                NotificationBatchService::add($firstStep->user, $reimb);
            }
        }

        return redirect()->route('travel_events.show', $travelEvent)->with('success', "Evento cerrado exitosamente. Se liberaron {$count} facturas para su cobro.");
    }
}
