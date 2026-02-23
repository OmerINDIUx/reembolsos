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
        $query = CostCenter::with(['director', 'controlObra', 'directorEjecutivo'])->orderBy('code');

        if ($user->role === 'director') {
            $query->where('director_id', $user->id);
        } elseif ($user->role === 'control_obra') {
            $query->where('control_obra_id', $user->id);
        } elseif ($user->role === 'director_ejecutivo') {
            $query->where('director_ejecutivo_id', $user->id);
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

        $directors = User::whereIn('role', ['admin', 'director'])->orderBy('name')->get();
        $controlObras = User::where('role', 'control_obra')->orderBy('name')->get();
        $executives = User::where('role', 'director_ejecutivo')->orderBy('name')->get();
        
        return view('cost_centers.create', compact('directors', 'controlObras', 'executives'));
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
            'director_id' => ['required', 'exists:users,id'],
            'control_obra_id' => ['required', 'exists:users,id'],
            'director_ejecutivo_id' => ['required', 'exists:users,id'],
        ]);

        $data = $request->all();
        $data['code'] = strtoupper(\Illuminate\Support\Str::slug($request->name));

        CostCenter::create($data);

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CostCenter $costCenter)
    {
        if (!Auth::user()->isAdmin()) {
             abort(403, 'Unauthorized action.');
        }

        $directors = User::whereIn('role', ['admin', 'director'])->orderBy('name')->get();
        $controlObras = User::where('role', 'control_obra')->orderBy('name')->get();
        $executives = User::where('role', 'director_ejecutivo')->orderBy('name')->get();

        return view('cost_centers.edit', compact('costCenter', 'directors', 'controlObras', 'executives'));
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
            'director_id' => ['required', 'exists:users,id'],
            'control_obra_id' => ['required', 'exists:users,id'],
            'director_ejecutivo_id' => ['required', 'exists:users,id'],
        ]);

        $data = $request->all();
        $data['code'] = strtoupper(\Illuminate\Support\Str::slug($request->name));

        $costCenter->update($data);

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos actualizado exitosamente.');
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
