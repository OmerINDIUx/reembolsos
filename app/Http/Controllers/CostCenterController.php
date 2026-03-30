<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CostCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Base query
        $query = CostCenter::with(['director', 'controlObra', 'directorEjecutivo', 'accountant', 'direccion', 'tesoreria'])->orderBy('code');

        if ($user->isAdmin() || $user->isAdminView()) {
            // Admin and AdminView see all
        } else {
            // See any cost center where user is part of the approval chain
            $query->whereHas('approvalSteps', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $costCenters = $query->paginate(10)->appends($request->all());
        return view('cost_centers.index', compact('costCenters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $users = User::orderBy('name')->get();
        return view('cost_centers.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cost_centers,name'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
        ]);

        $cc = CostCenter::create([
            'name' => $request->name,
            'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
            'description' => $request->description,
        ]);

        foreach ($request->steps as $index => $step) {
            $cc->approvalSteps()->create([
                'user_id' => $step['user_id'],
                'name' => $step['name'],
                'order' => $index + 1,
            ]);
        }

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos creado con ' . count($request->steps) . ' niveles de aprobación.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CostCenter $costCenter)
    {
        if (!Auth::user()->isAdmin()) {
             abort(403, 'Unauthorized action.');
        }

        $users = User::orderBy('name')->get();
        $costCenter->load('approvalSteps.user');
        return view('cost_centers.edit', compact('costCenter', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CostCenter $costCenter)
    {
        if (!Auth::user()->isAdmin()) {
             abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('cost_centers')->ignore($costCenter->id)],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
        ]);

        $costCenter->update([
            'name' => $request->name,
            'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
            'description' => $request->description,
        ]);

        // Rebuild steps
        $costCenter->approvalSteps()->delete();
        foreach ($request->steps as $index => $step) {
            $costCenter->approvalSteps()->create([
                'user_id' => $step['user_id'],
                'name' => $step['name'],
                'order' => $index + 1,
            ]);
        }

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos actualizado con ' . count($request->steps) . ' niveles de aprobación.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CostCenter $costCenter)
    {
        if (!Auth::user()->isAdmin()) {
             abort(403, 'Unauthorized action.');
        }

        $costCenter->delete();

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos eliminado.');
    }
}
