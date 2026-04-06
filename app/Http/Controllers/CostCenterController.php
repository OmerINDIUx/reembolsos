<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\User;
use App\Models\BudgetRenewal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CostCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Base query
        $query = CostCenter::with(['director', 'controlObra', 'directorEjecutivo', 'accountant', 'direccion', 'tesoreria'])
            ->withCount([
                'reimbursements as pending_count' => function($q) {
                    $q->whereNotIn('status', ['aprobado', 'rechazado']);
                },
                'reimbursements as approved_count' => function($q) {
                    $q->where('status', 'aprobado');
                },
                'approvalSteps'
            ])
            ->withSum([
                'reimbursements as pending_total' => function($q) {
                    $q->whereNotIn('status', ['aprobado', 'rechazado']);
                }
            ], 'total')
            ->withSum([
                'reimbursements as approved_total' => function($q) {
                    $q->where('status', 'aprobado');
                }
            ], 'total')
            ->withMin([
                'reimbursements as oldest_pending' => function($q) {
                    $q->whereNotIn('status', ['aprobado', 'rechazado']);
                }
            ], 'created_at')
            ->withAvg([
                'reimbursements as avg_approval_days' => function($q) {
                    $q->where('status', 'aprobado')->whereNotNull('approved_by_treasury_at');
                }
            ], DB::raw('TIMESTAMPDIFF(SECOND, created_at, approved_by_treasury_at) / 86400'))
            ->orderBy('code');

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

        // Detailed progress stats: where are the pending reimbursements?
        $stepBreakdown = \App\Models\Reimbursement::with('currentStep')
            ->whereIn('cost_center_id', $costCenters->pluck('id'))
            ->whereNotIn('status', ['aprobado', 'rechazado'])
            ->select('cost_center_id', 'current_step_id', DB::raw('count(*) as count'))
            ->groupBy('cost_center_id', 'current_step_id')
            ->get()
            ->groupBy('cost_center_id');



        return view('cost_centers.index', compact('costCenters', 'stepBreakdown'));
    }

    /**
     * Display the specified resource.
     */
    public function show(CostCenter $costCenter)
    {
        $costCenter->load(['director', 'controlObra', 'directorEjecutivo', 'accountant', 'direccion', 'tesoreria', 'approvalSteps.user']);

        // 1. Basic Stats
        $pendingQuery = $costCenter->reimbursements()->whereNotIn('status', ['aprobado', 'rechazado']);
        $approvedQuery = $costCenter->reimbursements()->where('status', 'aprobado');

        $stats = [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingQuery)->sum('total'),
            'approved_count' => (clone $approvedQuery)->count(),
            'approved_amount' => (clone $approvedQuery)->sum('total'),
            'correction_count' => $costCenter->reimbursements()->where('status', 'requiere_correccion')->count(),
            'rejected_count' => $costCenter->reimbursements()->where('status', 'rechazado')->count(),
            'avg_approval_days' => $approvedQuery->whereNotNull('approved_by_treasury_at')
                ->avg(DB::raw('TIMESTAMPDIFF(SECOND, created_at, approved_by_treasury_at) / 86400')) ?? 0,
        ];

        // 2. Status Breakdown (for chart/overview)
        $statusBreakdown = $costCenter->reimbursements()
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
            ->groupBy('status')
            ->get();

        // 3. Step Breakdown (Bottlenecks)
        $stepBreakdown = $costCenter->reimbursements()
            ->whereNotIn('status', ['aprobado', 'rechazado'])
            ->with('currentStep')
            ->select('current_step_id', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
            ->groupBy('current_step_id')
            ->get();

        // 4. Category Breakdown
        $categoryBreakdown = $costCenter->reimbursements()
            ->select('category', DB::raw('sum(total) as amount'), DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->get();

        // 5. Monthly Trend (Last 6 months)
        $monthlyTrend = $costCenter->reimbursements()
            ->where('status', 'aprobado')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('sum(total) as amount')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 6. Top Spenders in this CC
        $topSpenders = $costCenter->reimbursements()
            ->select('user_id', DB::raw('sum(total) as amount'), DB::raw('count(*) as count'))
            ->with('user')
            ->groupBy('user_id')
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        // 7. Recent Activity
        $recentReimbursements = $costCenter->reimbursements()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 8. Budget Renewals
        $budgetRenewals = $costCenter->budgetRenewals()->with('user')->get();

        return view('cost_centers.show', compact('costCenter', 'stats', 'statusBreakdown', 'stepBreakdown', 'categoryBreakdown', 'monthlyTrend', 'topSpenders', 'recentReimbursements', 'budgetRenewals'));
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
            'budget' => ['required', 'numeric', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
        ]);

        $cc = CostCenter::create([
            'name' => $request->name,
            'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
            'description' => $request->description,
            'budget' => $request->budget,
        ]);

        // Create initial renewal record
        $cc->budgetRenewals()->create([
            'amount' => $request->budget,
            'description' => 'Presupuesto inicial',
            'renewal_date' => now(),
            'user_id' => Auth::id(),
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
            'budget' => ['required', 'numeric', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
        ]);

        $costCenter->update([
            'name' => $request->name,
            'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
            'description' => $request->description,
            'budget' => $request->budget,
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

    /**
     * Add funds to the cost center budget.
     */
    public function renewBudget(Request $request, CostCenter $costCenter)
    {
        if (!Auth::user()->hasRole('admin', 'control_obra')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'renewal_date' => ['required', 'date'],
        ]);

        DB::transaction(function() use ($request, $costCenter) {
            // Create renewal record
            $costCenter->budgetRenewals()->create([
                'amount' => $request->amount,
                'description' => $request->description,
                'renewal_date' => $request->renewal_date,
                'user_id' => Auth::id(),
            ]);

            // Update total budget
            $costCenter->increment('budget', $request->amount);
        });

        return redirect()->back()->with('success', 'Presupuesto renovado correctamente.');
    }
}
