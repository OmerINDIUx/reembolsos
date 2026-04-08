<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('director')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('must_change_password', 0);
            } elseif ($request->status === 'inactive') {
                $query->where('must_change_password', 1);
            }
        }

        $users = $query->paginate(10)->appends($request->all());
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $directors = User::whereIn('role', ['admin', 'director'])->get();
        return view('users.create', compact('directors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,admin_view,director,accountant,user,tesoreria,control_obra,director_ejecutivo,direccion'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'clabe' => ['nullable', 'string', 'size:18', 'regex:/^[0-9]+$/'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'bank_name' => $request->bank_name,
            'clabe' => $request->clabe,
            'must_change_password' => ($request->password === 'S20hg00146'),
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['director', 'subordinates', 'costCenters']);

        // 1. Personal Spending Stats
        $pendingQuery = $user->reimbursements()->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);
        $approvedQuery = $user->reimbursements()->where('status', 'aprobado');

        $stats = [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingQuery)->sum('total'),
            'approved_count' => (clone $approvedQuery)->count(),
            'approved_amount' => (clone $approvedQuery)->sum('total'),
            'rejected_count' => $user->reimbursements()->where('status', 'rechazado')->count(),
        ];

        // 2. Category Breakdown (Personal)
        $categoryBreakdown = $user->reimbursements()
            ->where('status', '!=', 'borrador')
            ->select('category', DB::raw('sum(total) as amount'), DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->get();

        // 3. Status Breakdown
        $statusBreakdown = $user->reimbursements()
            ->where('status', '!=', 'borrador')
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
            ->groupBy('status')
            ->get();

        // 4. Monthly Trend (Last 6 months)
        $monthlyTrend = $user->reimbursements()
            ->where('status', 'aprobado')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('sum(total) as amount')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 5. Recent Activity
        $recentReimbursements = $user->reimbursements()
            ->where('status', '!=', 'borrador')
            ->with(['costCenter', 'currentStep'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 6. Approval Task Stats (if they are an approver)
        // This is complex as it depends on current_step_id pointing to a step they are assigned to
        $pendingApprovalsCount = \App\Models\Reimbursement::whereHas('currentStep', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->count();

        return view('users.show', compact('user', 'stats', 'categoryBreakdown', 'statusBreakdown', 'monthlyTrend', 'recentReimbursements', 'pendingApprovalsCount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $directors = User::whereIn('role', ['admin', 'director'])->where('id', '!=', $user->id)->get();
        return view('users.edit', compact('user', 'directors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'in:admin,admin_view,director,accountant,user,tesoreria,control_obra,director_ejecutivo,direccion'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'clabe' => ['nullable', 'string', 'size:18', 'regex:/^[0-9]+$/'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'bank_name' => $request->bank_name,
            'clabe' => $request->clabe,
        ];

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            $data['password'] = Hash::make($request->password);
            $data['must_change_password'] = ($request->password === 'S20hg00146');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->getKey() === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}
