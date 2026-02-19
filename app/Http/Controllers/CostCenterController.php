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
        $query = CostCenter::with('director')->orderBy('code');

        if ($user->role === 'director' || $user->role === 'user') {
            // Filter by director_id (for director users, it's their id. For normal users, it's their director_id)
            $directorId = ($user->role === 'director') ? $user->id : $user->director_id;
            
            if ($directorId) {
                $query->where('director_id', $directorId);
            } else {
                 // If a normal user has no director assigned, they see nothing or everything? 
                 // Assuming they see nothing to be safe, or just public ones (if any).
                 // For now, let's return empty if no director assigned finding logic.
                 if ($user->role === 'user') $query->where('id', 0); 
            }
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
