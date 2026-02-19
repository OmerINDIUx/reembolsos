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
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isAdmin() || $user->role === 'accountant') {
            $costCenters = CostCenter::with('director')->orderBy('code')->paginate(10);
        } elseif ($user->isDirector()) {
            $costCenters = CostCenter::with('director')->where('director_id', $user->id)->orderBy('code')->paginate(10);
        } else {
            // Regular user sees only their director's cost centers? Or shouldn't handle cost centers?
            // The request says: "usuario ligado al director solo ve los centros de costos del director ligado"
            // This is for SELECTION, but if they access index, show only relevant ones.
            if ($user->director_id) {
                $costCenters = CostCenter::with('director')->where('director_id', $user->director_id)->orderBy('code')->paginate(10);
            } else {
                $costCenters = CostCenter::where('id', 0)->paginate(10); // Empty
            }
        }

        return view('cost_centers.index', compact('costCenters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only Admin usually creates cost centers, maybe Director?
        // Let's allow Admin.
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $directors = User::whereIn('role', ['admin', 'director'])->orderBy('name')->get();
        return view('cost_centers.create', compact('directors'));
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
        ]);

        $data = $request->all();
        // Auto-generate code from name
        $data['code'] = strtoupper(\Illuminate\Support\Str::slug($request->name));
        // Ensure code uniqueness by appending random string if needed? 
        // For now assume names are unique enough or let db fail on duplicate code constraint 
        // (but we validated name as unique).

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
        return view('cost_centers.edit', compact('costCenter', 'directors'));
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
