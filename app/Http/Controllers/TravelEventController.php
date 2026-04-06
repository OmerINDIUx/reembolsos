<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\TravelEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        return view('travel_events.create', compact('directors', 'costCenters', 'users'));
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
    public function show(TravelEvent $travelEvent)
    {
        return view('travel_events.show', compact('travelEvent'));
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
}
