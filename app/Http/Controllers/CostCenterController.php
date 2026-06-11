<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Company;
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
        $query = CostCenter::with(['beneficiary', 'company', 'approvalSteps.user'])
            ->withCount([
                'reimbursements as pending_count' => function($q) {
                    $q->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);
                },
                'reimbursements as approved_count' => function($q) {
                    $q->whereIn('status', ['aprobado', 'pagado']);
                },
                'reimbursements',
                'approvalSteps'
            ])
            ->withSum([
                'reimbursements as pending_total' => function($q) {
                    $q->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);
                }
            ], 'total')
            ->withSum([
                'reimbursements as approved_total' => function($q) {
                    $q->whereIn('status', ['aprobado', 'pagado']);
                }
            ], 'total')
            ->withMin([
                'reimbursements as oldest_pending' => function($q) {
                    $q->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);
                }
            ], 'created_at')
            ->withAvg([
                'reimbursements as avg_approval_days' => function($q) {
                    $q->whereIn('status', ['aprobado', 'pagado'])->whereNotNull('approved_by_treasury_at');
                }
            ], DB::raw('TIMESTAMPDIFF(SECOND, created_at, approved_by_treasury_at) / 86400'))
            ->where('is_active', $request->get('tab') === 'history' ? false : true)
            ->orderBy('code');

        // Admin, AdminView, Subdirección (N4), Dirección General (N5) see ALL
        if (!$user->hasRole('admin', 'admin_view', 'accountant', 'direccion')) {
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
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->where('name', 'like', "%{$search}%")
                          ->orWhere('account', 'like', "%{$search}%");
                  });
            });
        }

        $costCenters = $query->paginate(10)->appends($request->all());

        // Detailed progress stats: where are the pending reimbursements?
        $stepBreakdown = \App\Models\Reimbursement::with('currentStep')
            ->whereIn('cost_center_id', $costCenters->pluck('id'))
            ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])
            ->select('cost_center_id', 'current_step_id', DB::raw('count(*) as count'))
            ->groupBy('cost_center_id', 'current_step_id')
            ->get()
            ->groupBy('cost_center_id');



        return view('cost_centers.index', compact('costCenters', 'stepBreakdown'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, CostCenter $costCenter)
    {
        if (!$request->filled('period_type')) {
            $request->merge([
                'period_type' => 'month',
                'period_month' => now()->format('Y-m')
            ]);
        }

        $periods = \App\Models\Reimbursement::getAvailableTimePeriods();
        $costCenter->load(['beneficiary', 'approvalSteps.user']);

        // 1. Basic Stats
        // ONLY marked users affect the budget
        $markedUserIds = $costCenter->authorizedUsers()->wherePivot('can_do_special', true)->pluck('users.id');

        $pendingQuery = $costCenter->reimbursements()->applyTimeFilters($request)->whereNotIn('status', ['aprobado', 'pagado', 'rechazado', 'borrador']);
        $approvedQuery = $costCenter->reimbursements()->applyTimeFilters($request)->whereIn('status', ['aprobado', 'pagado']);

        $pendingBudgetQuery = clone $pendingQuery;
        $approvedBudgetQuery = clone $approvedQuery;

        $stats = [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingBudgetQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'approved_count' => (clone $approvedQuery)->count(),
            'approved_amount' => (clone $approvedBudgetQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'correction_count' => $costCenter->reimbursements()->applyTimeFilters($request)->where('status', 'requiere_correccion')->count(),
            'rejected_count' => $costCenter->reimbursements()->applyTimeFilters($request)->where('status', 'rechazado')->count(),
            'avg_approval_days' => $approvedQuery->whereNotNull('approved_by_treasury_at')
                ->avg(DB::raw('TIMESTAMPDIFF(SECOND, created_at, approved_by_treasury_at) / 86400')) ?? 0,
        ];

        // 2. Status Breakdown (for chart/overview)
        $statusBreakdown = $costCenter->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->groupBy('status')
            ->get();

        // 3. Step Breakdown (Bottlenecks)
        $stepBreakdown = $costCenter->reimbursements()
            ->applyTimeFilters($request)
            ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])
            ->with('currentStep')
            ->select('current_step_id', DB::raw('count(*) as count'), DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->groupBy('current_step_id')
            ->get();

        // 4. Category Breakdown
        $categoryBreakdown = $costCenter->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select('category', DB::raw('sum(total + COALESCE(propina, 0)) as amount'), DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->get();

        // 5. Monthly Trend (Last 6 months)
        $monthlyTrend = $costCenter->reimbursements()
            ->where('status', 'aprobado')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('sum(total + COALESCE(propina, 0)) as amount')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 6. Top Spenders in this CC
        $topSpenders = $costCenter->reimbursements()
            ->where('status', '!=', 'borrador')
            ->select('user_id', DB::raw('sum(total + COALESCE(propina, 0)) as amount'), DB::raw('count(*) as count'))
            ->with('user')
            ->groupBy('user_id')
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        // 7. Recent Activity
        $recentReimbursements = $costCenter->reimbursements()
            ->where('status', '!=', 'borrador')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 8. Budget Renewals
        $budgetRenewals = $costCenter->budgetRenewals()->with('user')->get();

        return view('cost_centers.show', compact('costCenter', 'stats', 'statusBreakdown', 'stepBreakdown', 'categoryBreakdown', 'monthlyTrend', 'topSpenders', 'recentReimbursements', 'budgetRenewals', 'periods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion')) {
            abort(403, 'Unauthorized action.');
        }

        $users = User::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        return view('cost_centers.create', compact('users', 'companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion')) {
            abort(403, 'Unauthorized action.');
        }
        $request->merge([
            'menfis_email' => filled($request->menfis_email) ? trim($request->menfis_email) : null,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cost_centers,name'],
            'company_id' => ['required', 'exists:companies,id'],
            'beneficiary_id' => ['nullable', 'exists:users,id'],
            'budget' => ['required', 'numeric', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'menfis_email' => ['nullable', 'email', 'max:255'],
            'allowed_users' => ['nullable', 'array'],
            'allowed_users.*.user_id' => ['required', 'exists:users,id'],
            'allowed_users.*.can_do_special' => ['nullable'],
        ]);

        $cc = CostCenter::create([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
            'description' => $request->description,
            'menfis_email' => $request->menfis_email,
            'budget' => $request->budget,
            'beneficiary_id' => $request->beneficiary_id,
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

        if ($request->has('allowed_users')) {
            $syncData = [];
            foreach ($request->allowed_users as $user) {
                $syncData[$user['user_id']] = [
                    'can_do_special' => isset($user['can_do_special']) && $user['can_do_special'] ? true : false
                ];
            }
            $cc->authorizedUsers()->sync($syncData);
        }

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos creado con ' . count($request->steps) . ' niveles de aprobación.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CostCenter $costCenter)
    {
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion')) {
             abort(403, 'Unauthorized action.');
        }

        $users = User::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        $costCenter->load('approvalSteps.user');
        return view('cost_centers.edit', compact('costCenter', 'users', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CostCenter $costCenter)
    {
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion')) {
             abort(403, 'Unauthorized action.');
        }

        $request->merge([
            'menfis_email' => filled($request->menfis_email) ? trim($request->menfis_email) : null,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('cost_centers')->ignore($costCenter->id)],
            'company_id' => ['required', 'exists:companies,id'],
            'beneficiary_id' => ['nullable', 'exists:users,id'],
            'budget' => ['required', 'numeric', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.id' => [
                'nullable',
                'integer',
                Rule::exists('approval_steps', 'id')->where(fn ($query) => $query->where('cost_center_id', $costCenter->id)),
            ],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'menfis_email' => ['nullable', 'email', 'max:255'],
            'allowed_users' => ['nullable', 'array'],
            'allowed_users.*.user_id' => ['required', 'exists:users,id'],
            'allowed_users.*.can_do_special' => ['nullable'],
        ]);

        DB::transaction(function() use ($request, $costCenter) {
            // 1. Capture the current approval chain before any deletion can null current_step_id.
            $existingSteps = $costCenter->approvalSteps()
                ->orderBy('order')
                ->get();
            $existingStepsById = $existingSteps->keyBy('id');
            $oldSteps = $existingSteps
                ->map(fn ($step) => (object) [
                    'id' => $step->id,
                    'user_id' => $step->user_id,
                    'name' => $step->name,
                    'order' => $step->order,
                ]);
            $oldStepsById = $oldSteps->keyBy('id');

            $pendingReimbursements = $costCenter->reimbursements()
                ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador', 'pendiente_revision_cxp', 'pendiente_pago', 'pagado'])
                ->whereNotNull('current_step_id')
                ->with('currentStep')
                ->get();

            // 2. Update CC Basic Info
            $costCenter->update([
                'name' => $request->name,
                'company_id' => $request->company_id,
                'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
                'description' => $request->description,
                'menfis_email' => $request->menfis_email,
                'budget' => $request->budget,
                'beneficiary_id' => $request->beneficiary_id,
            ]);

            // 3. Update existing steps, create new ones, and only delete removed levels.
            $submittedStepIds = collect($request->steps)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();
            $submittedHasExistingIds = $submittedStepIds->isNotEmpty();
            $savedSteps = collect();

            foreach ($request->steps as $index => $step) {
                $approvalStep = null;

                if (!empty($step['id'])) {
                    $approvalStep = $existingStepsById->get((int) $step['id']);
                }

                if ($approvalStep) {
                    $approvalStep->update([
                        'user_id' => $step['user_id'],
                        'name' => $step['name'],
                        'order' => $index + 1,
                    ]);
                } else {
                    $approvalStep = $costCenter->approvalSteps()->create([
                        'user_id' => $step['user_id'],
                        'name' => $step['name'],
                        'order' => $index + 1,
                    ]);
                }

                $savedSteps->push($approvalStep->fresh());
            }

            $keptOldStepIds = $savedSteps
                ->pluck('id')
                ->intersect($oldSteps->pluck('id'))
                ->values();
            $keptOldStepIdList = $keptOldStepIds->all();

            // 4. Rescue pending reimbursements before deleting removed steps.
            foreach ($pendingReimbursements as $r) {
                $oldStep = $oldStepsById->get($r->current_step_id);

                if (!$oldStep || in_array($oldStep->id, $keptOldStepIdList, false)) {
                    continue;
                }

                $newStep = null;

                if ($submittedHasExistingIds) {
                    $nextKeptOldStep = $oldSteps
                        ->whereIn('id', $keptOldStepIdList)
                        ->where('order', '>', $oldStep->order)
                        ->sortBy('order')
                        ->first();

                    $newStep = $nextKeptOldStep
                        ? $savedSteps->firstWhere('id', $nextKeptOldStep->id)
                        : null;
                } else {
                    $newStep = $savedSteps
                        ->first(fn ($step) => $step->user_id == $oldStep->user_id && $step->name === $oldStep->name);

                    if (!$newStep) {
                        $nextOldStep = $oldSteps
                            ->where('order', '>', $oldStep->order)
                            ->first(fn ($step) => $savedSteps->contains(fn ($saved) => $saved->user_id == $step->user_id && $saved->name === $step->name));

                        $newStep = $nextOldStep
                            ? $savedSteps->first(fn ($saved) => $saved->user_id == $nextOldStep->user_id && $saved->name === $nextOldStep->name)
                            : null;
                    }
                }

                if ($newStep) {
                    $r->update(['current_step_id' => $newStep->id]);
                } else {
                    $r->update([
                        'current_step_id' => null,
                        'status' => 'pendiente_revision_cxp',
                    ]);
                }
            }

            $costCenter->approvalSteps()
                ->whereNotIn('id', $savedSteps->pluck('id'))
                ->delete();

            // 5. Sync authorized users
            if ($request->has('allowed_users')) {
                $syncData = [];
                foreach ($request->allowed_users as $user) {
                    $syncData[$user['user_id']] = [
                        'can_do_special' => isset($user['can_do_special']) && $user['can_do_special'] ? true : false
                    ];
                }
                $costCenter->authorizedUsers()->sync($syncData);
            } else {
                $costCenter->authorizedUsers()->sync([]);
            }
        });

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos actualizado con ' . count($request->steps) . ' niveles de aprobación.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CostCenter $costCenter)
    {
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion')) {
             abort(403, 'Unauthorized action.');
        }

        if ($costCenter->reimbursements()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar un centro de costos con reembolsos asociados. Considere desactivarlo en su lugar.');
        }

        $costCenter->delete();

        return redirect()->route('cost_centers.index')->with('success', 'Centro de Costos eliminado.');
    }

    /**
     * Toggle the active status of a cost center.
     */
    public function toggleStatus(CostCenter $costCenter)
    {
        if (!Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'accountant', 'direccion')) {
             abort(403, 'Unauthorized action.');
        }

        $costCenter->is_active = !$costCenter->is_active;
        $costCenter->save();

        $status = $costCenter->is_active ? 'activado' : 'desactivado (enviado a Historial)';
        return redirect()->back()->with('success', "Centro de Costos {$status} correctamente.");
    }

    /**
     * Add funds to the cost center budget.
     */
    public function renewBudget(Request $request, CostCenter $costCenter)
    {
        if (!Auth::user()->hasRole('admin', 'control_obra', 'accountant', 'direccion')) {
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
