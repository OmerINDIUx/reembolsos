<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Company;
use App\Models\ReimbursementApproval;
use App\Models\FixedFund;
use App\Models\User;
use App\Models\BudgetRenewal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CostCenterController extends Controller
{
    private const FIXED_FUND_TRANSFER_BLOCKED_STATUSES = [
        'borrador',
        'rechazado',
        'aprobado',
        'pagado',
    ];

    private function syncFixedFunds(CostCenter $costCenter, array $funds, array $transfers = []): void
    {
        $keptIds = collect($funds)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        $existingFunds = $costCenter->fixedFunds()->lockForUpdate()->get()->keyBy('id');
        $transfersByFund = collect($transfers)
            ->filter(fn ($transfer) => !empty($transfer['fund_id']) && !empty($transfer['transfer_to_user_id']))
            ->keyBy(fn ($transfer) => (int) $transfer['fund_id']);
        $savedFunds = collect();

        $submittedUserIds = collect($funds)->pluck('user_id')->map(fn ($id) => (int) $id)->filter()->values();

        foreach ($funds as $fund) {
            $values = [
                'user_id' => $fund['user_id'],
                'name' => $fund['name'],
                'budget' => $fund['budget'],
                'is_active' => true,
            ];

            if (!empty($fund['id'])) {
                $fixedFund = $costCenter->fixedFunds()->whereKey($fund['id'])->firstOrFail();
                $oldUserId = (int) $fixedFund->user_id;
                $newUserId = (int) $fund['user_id'];

                $fixedFund->update($values);

                if ($oldUserId !== $newUserId) {
                    $this->transferActiveFixedFundReimbursements($fixedFund, $fixedFund, $newUserId);
                }
            } else {
                $fixedFund = $costCenter->fixedFunds()->updateOrCreate(['user_id' => $fund['user_id']], $values);
            }

            $savedFunds->push($fixedFund->fresh());
        }

        $savedFunds->each(fn ($fund) => $this->syncFixedFundPaymentRecipient($fund));

        $removedFunds = $existingFunds
            ->filter(fn ($fund) => $fund->is_active && !$keptIds->contains((int) $fund->id));

        foreach ($removedFunds as $removedFund) {
            $activeReimbursements = $this->activeFixedFundReimbursements($removedFund);
            $transfer = $transfersByFund->get((int) $removedFund->id);

            if ($activeReimbursements->exists()) {
                if (!$transfer) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fund_transfers' => "Selecciona quién recibirá los reembolsos activos de {$removedFund->name}.",
                    ]);
                }

                $targetUserId = (int) $transfer['transfer_to_user_id'];

                if ((int) $removedFund->user_id === $targetUserId || !$submittedUserIds->contains($targetUserId)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fund_transfers' => 'El receptor debe ser otro responsable que permanezca activo en este centro de costos.',
                    ]);
                }

                $targetFund = $savedFunds->first(fn ($fund) => (int) $fund->user_id === $targetUserId && $fund->is_active)
                    ?: $costCenter->fixedFunds()->where('user_id', $targetUserId)->where('is_active', true)->first();

                if (!$targetFund) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fund_transfers' => 'No se encontró un fondo activo para recibir los reembolsos.',
                    ]);
                }

                $this->transferActiveFixedFundReimbursements($removedFund, $targetFund, $targetUserId);
            }
        }

        $costCenter->fixedFunds()->whereNotIn('id', $savedFunds->pluck('id'))->update(['is_active' => false]);
    }

    private function activeFixedFundReimbursements(FixedFund $fixedFund)
    {
        return $fixedFund->reimbursements()
            ->where('type', 'fondo_fijo')
            ->whereNotIn('status', self::FIXED_FUND_TRANSFER_BLOCKED_STATUSES);
    }

    private function transferActiveFixedFundReimbursements(FixedFund $sourceFund, FixedFund $targetFund, int $targetUserId): void
    {
        $this->activeFixedFundReimbursements($sourceFund)->update([
            'fixed_fund_id' => $targetFund->id,
            'payee_id' => $targetUserId,
        ]);
    }

    private function syncFixedFundPaymentRecipient(FixedFund $fixedFund): void
    {
        $this->activeFixedFundReimbursements($fixedFund)
            ->where(function ($query) use ($fixedFund) {
                $query->whereNull('payee_id')
                    ->orWhere('payee_id', '<>', $fixedFund->user_id);
            })
            ->update(['payee_id' => $fixedFund->user_id]);
    }

    private function ensureFixedFundUsersCanReceive(array $funds): void
    {
        $userIds = collect($funds)->pluck('user_id')->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $blockedUser = User::whereIn('id', $userIds)
            ->get()
            ->first(fn ($user) => $user->hasRole('tesoreria'));

        if ($blockedUser) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fixed_funds' => 'Cuentas por Pagar Pagadores no puede recibir la asignación de un fondo fijo.',
            ]);
        }
    }

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
        $costCenter->load(['beneficiary', 'fixedFunds.user', 'approvalSteps.user']);

        // 1. Basic Stats
        // ONLY marked users affect the budget
        $markedUserIds = $costCenter->authorizedUsers()->wherePivot('can_do_special', true)->pluck('users.id');

        $pendingQuery = $costCenter->reimbursements()->applyTimeFilters($request)->whereNotIn('status', ['aprobado', 'pagado', 'rechazado', 'borrador']);
        $approvedQuery = $costCenter->reimbursements()->applyTimeFilters($request)->whereIn('status', ['aprobado', 'pagado']);
        $correctionQuery = $costCenter->reimbursements()->applyTimeFilters($request)->where('status', 'requiere_correccion');
        $rejectedQuery = $costCenter->reimbursements()->applyTimeFilters($request)->where('status', 'rechazado');

        $stats = [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'approved_count' => (clone $approvedQuery)->count(),
            'approved_amount' => (clone $approvedQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'correction_count' => (clone $correctionQuery)->count(),
            'correction_amount' => (clone $correctionQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'rejected_count' => (clone $rejectedQuery)->count(),
            'rejected_amount' => (clone $rejectedQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
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

        $approverEfficiency = $this->getApproverEfficiency($request, $costCenter);
        $stats['avg_decision_minutes'] = $approverEfficiency['summary']->avg_approval_minutes;

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
        $fundSummaries = $costCenter->fixedFunds()->where('is_active', true)->with('user')
            ->withSum(['reimbursements as spent_total' => fn ($query) => $query->whereNotIn('status', ['borrador', 'rechazado'])], 'total')
            ->withSum(['reimbursements as spent_tips' => fn ($query) => $query->whereNotIn('status', ['borrador', 'rechazado'])], 'propina')
            ->get();

        return view('cost_centers.show', compact('costCenter', 'stats', 'statusBreakdown', 'stepBreakdown', 'categoryBreakdown', 'monthlyTrend', 'topSpenders', 'recentReimbursements', 'budgetRenewals', 'fundSummaries', 'periods', 'approverEfficiency'));
    }

    private function getApproverEfficiency(Request $request, CostCenter $costCenter): array
    {
        $decisionEventsQuery = ReimbursementApproval::query()
            ->whereIn('action', ['aprobado', 'rechazado', 'requiere_correccion'])
            ->whereHas('reimbursement', fn ($query) => $query->where('cost_center_id', $costCenter->id))
            ->with(['user', 'reimbursement.approvals']);

        $this->applyApprovalEventPeriod($decisionEventsQuery, $request);

        $decisionEvents = $decisionEventsQuery
            ->get()
            ->map(function (ReimbursementApproval $decision) {
                $reimbursement = $decision->reimbursement;
                if (! $reimbursement) {
                    return null;
                }

                $previousEvent = $reimbursement->approvals
                    ->filter(fn (ReimbursementApproval $event) =>
                        $event->created_at->lt($decision->created_at)
                        || ($event->created_at->equalTo($decision->created_at) && $event->id < $decision->id)
                    )
                    ->last();

                $startedAt = $previousEvent?->created_at ?? $reimbursement->created_at;

                return (object) [
                    'id' => $decision->id,
                    'reimbursement_id' => $decision->reimbursement_id,
                    'user_id' => $decision->user_id,
                    'user' => $decision->user,
                    'step_name' => $decision->step_name,
                    'action' => $decision->action,
                    'amount' => (float) $reimbursement->total + (float) ($reimbursement->propina ?? 0),
                    'elapsed_minutes' => max(0, $startedAt->diffInMinutes($decision->created_at)),
                ];
            })
            ->filter();

        $activeItems = $costCenter->reimbursements()
            ->applyTimeFilters($request)
            ->whereNotIn('status', ['aprobado', 'pagado', 'rechazado', 'borrador'])
            ->with('currentStep')
            ->get(['id', 'status', 'current_step_id', 'total', 'propina', 'approved_by_treasury_at'])
            ->map(function ($reimbursement) use ($costCenter) {
                $assignedUserId = $reimbursement->currentStep?->user_id;
                $queueLabel = null;

                if (! $assignedUserId && $reimbursement->status === 'pendiente_revision_cxp') {
                    $assignedUserId = $costCenter->accountant_id;
                    $queueLabel = $assignedUserId ? null : 'Cuentas por Pagar · Revisión';
                }

                if (
                    ! $assignedUserId
                    && $reimbursement->status === 'pendiente_pago'
                    && $reimbursement->approved_by_treasury_at === null
                ) {
                    $assignedUserId = $costCenter->tesoreria_id;
                    $queueLabel = $assignedUserId ? null : 'Cuentas por Pagar · Pago';
                }

                if (
                    ! $assignedUserId
                    && $reimbursement->status === 'pendiente_pago'
                    && $reimbursement->approved_by_treasury_at !== null
                ) {
                    $queueLabel = 'Tesorería · Listo para pago';
                }

                return (object) [
                    'reimbursement_id' => $reimbursement->id,
                    'assigned_user_id' => $assignedUserId,
                    'queue_label' => $queueLabel,
                    'amount' => (float) $reimbursement->total + (float) ($reimbursement->propina ?? 0),
                ];
            });

        $configuredByUser = $costCenter->approvalSteps
            ->filter(fn ($step) => $step->user_id && $step->user)
            ->groupBy('user_id');

        $approverIds = $configuredByUser->keys()
            ->merge($decisionEvents->pluck('user_id'))
            ->merge($activeItems->pluck('assigned_user_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $rows = $approverIds
            ->map(function (int $userId) use ($configuredByUser, $decisionEvents, $activeItems) {
                $configuredSteps = $configuredByUser->get($userId, collect());
                $personEvents = $decisionEvents->where('user_id', $userId);
                $approvedEvents = $personEvents->where('action', 'aprobado');
                $rejectedEvents = $personEvents->where('action', 'rechazado');
                $correctionEvents = $personEvents->where('action', 'requiere_correccion');
                $pendingItems = $activeItems->where('assigned_user_id', $userId);
                $user = $configuredSteps->first()?->user ?? $personEvents->first()?->user ?? User::withTrashed()->find($userId);

                if (! $user) {
                    return null;
                }

                $decisionCount = $personEvents->count();
                $uniqueApprovedRequests = $approvedEvents->unique('reimbursement_id');
                $uniqueRejectedRequests = $rejectedEvents->unique('reimbursement_id');

                return (object) [
                    'user' => $user,
                    'sort_order' => $configuredSteps->min('order') ?? 999,
                    'step_names' => $configuredSteps->pluck('name')
                        ->merge($personEvents->pluck('step_name'))
                        ->filter()
                        ->unique()
                        ->values(),
                    'avg_approval_minutes' => $approvedEvents->isNotEmpty()
                        ? (float) $approvedEvents->avg('elapsed_minutes')
                        : null,
                    'approval_count' => $approvedEvents->count(),
                    'approved_request_count' => $uniqueApprovedRequests->count(),
                    'approved_amount' => (float) $uniqueApprovedRequests->sum('amount'),
                    'rejection_count' => $rejectedEvents->count(),
                    'rejected_amount' => (float) $uniqueRejectedRequests->sum('amount'),
                    'correction_count' => $correctionEvents->count(),
                    'pending_count' => $pendingItems->count(),
                    'pending_amount' => (float) $pendingItems->sum('amount'),
                    'approval_rate' => $decisionCount > 0
                        ? ($approvedEvents->count() / $decisionCount) * 100
                        : null,
                    'instant_approval_count' => $approvedEvents->where('elapsed_minutes', '<', 1)->count(),
                ];
            })
            ->filter()
            ->sortBy([
                ['sort_order', 'asc'],
                [fn ($row) => $row->user->name, 'asc'],
            ])
            ->values();

        $approvedDecisions = $decisionEvents->where('action', 'aprobado');
        $operationalQueues = $activeItems
            ->whereNull('assigned_user_id')
            ->whereNotNull('queue_label')
            ->groupBy('queue_label')
            ->map(fn ($items, $label) => (object) [
                'label' => $label,
                'count' => $items->count(),
                'amount' => (float) $items->sum('amount'),
            ])
            ->values();
        $unassignedPending = $activeItems
            ->whereNull('assigned_user_id')
            ->whereNull('queue_label');

        return [
            'rows' => $rows,
            'operational_queues' => $operationalQueues,
            'summary' => (object) [
                'avg_approval_minutes' => $approvedDecisions->isNotEmpty()
                    ? (float) $approvedDecisions->avg('elapsed_minutes')
                    : null,
                'approval_count' => $approvedDecisions->count(),
                'rejection_count' => $decisionEvents->where('action', 'rechazado')->count(),
                'correction_count' => $decisionEvents->where('action', 'requiere_correccion')->count(),
                'instant_approval_count' => $approvedDecisions->where('elapsed_minutes', '<', 1)->count(),
                'unique_requests' => $decisionEvents->pluck('reimbursement_id')->unique()->count(),
                'queue_pending_count' => $operationalQueues->sum('count'),
                'queue_pending_amount' => (float) $operationalQueues->sum('amount'),
                'unassigned_pending_count' => $unassignedPending->count(),
                'unassigned_pending_amount' => (float) $unassignedPending->sum('amount'),
            ],
        ];
    }

    private function applyApprovalEventPeriod($query, Request $request): void
    {
        switch ($request->input('period_type')) {
            case 'week':
                if ($request->filled('period_week')) {
                    $query->whereHas('reimbursement', fn ($reimbursement) =>
                        $reimbursement->where('week', $request->input('period_week'))
                    );
                }
                break;
            case 'month':
                if ($request->filled('period_month')) {
                    $date = \Carbon\Carbon::parse($request->input('period_month'));
                    $query->whereMonth('reimbursement_approvals.created_at', $date->month)
                        ->whereYear('reimbursement_approvals.created_at', $date->year);
                }
                break;
            case 'quarter':
                if ($request->filled('period_quarter')) {
                    [$year, $quarter] = array_pad(explode('-Q', $request->input('period_quarter')), 2, null);
                    if ($year && $quarter) {
                        $query->whereYear('reimbursement_approvals.created_at', $year)
                            ->where(DB::raw('QUARTER(reimbursement_approvals.created_at)'), $quarter);
                    }
                }
                break;
            case 'year':
                if ($request->filled('period_year')) {
                    $query->whereYear('reimbursement_approvals.created_at', $request->input('period_year'));
                }
                break;
        }
    }

    public function categoryMatrix(Request $request, CostCenter $costCenter)
    {
        if (! $request->filled('period_type')) {
            $request->merge([
                'period_type' => 'month',
                'period_month' => now()->format('Y-m'),
            ]);
        }

        $periods = \App\Models\Reimbursement::getAvailableTimePeriods();

        $categories = $costCenter->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select(
                'category',
                DB::raw('count(*) as count'),
                DB::raw('sum(total + COALESCE(propina, 0)) as amount'),
                DB::raw("sum(case when status in ('aprobado', 'pagado') then 1 else 0 end) as approved_count"),
                DB::raw("sum(case when status in ('aprobado', 'pagado') then total + COALESCE(propina, 0) else 0 end) as approved_amount"),
                DB::raw("sum(case when status = 'rechazado' then 1 else 0 end) as rejected_count"),
                DB::raw("sum(case when status = 'rechazado' then total + COALESCE(propina, 0) else 0 end) as rejected_amount"),
                DB::raw("sum(case when status not in ('aprobado', 'pagado', 'rechazado', 'borrador') then 1 else 0 end) as pending_count"),
                DB::raw("sum(case when status not in ('aprobado', 'pagado', 'rechazado', 'borrador') then total + COALESCE(propina, 0) else 0 end) as pending_amount")
            )
            ->groupBy('category')
            ->orderByDesc('amount')
            ->get();

        $totals = (object) [
            'categories' => $categories->count(),
            'count' => $categories->sum('count'),
            'amount' => (float) $categories->sum('amount'),
            'approved_count' => $categories->sum('approved_count'),
            'approved_amount' => (float) $categories->sum('approved_amount'),
            'rejected_count' => $categories->sum('rejected_count'),
            'rejected_amount' => (float) $categories->sum('rejected_amount'),
            'pending_count' => $categories->sum('pending_count'),
            'pending_amount' => (float) $categories->sum('pending_amount'),
        ];

        return view('cost_centers.category-matrix', compact('costCenter', 'categories', 'totals', 'periods'));
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
            'fixed_funds' => ['nullable', 'array'],
            'fixed_funds.*.user_id' => ['required', 'exists:users,id'],
            'fixed_funds.*.name' => ['required', 'string', 'max:255'],
            'fixed_funds.*.budget' => ['required', 'numeric', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.user_id' => ['required', 'exists:users,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'menfis_email' => ['nullable', 'email', 'max:255'],
            'allowed_users' => ['nullable', 'array'],
            'allowed_users.*.user_id' => ['required', 'exists:users,id'],
            'allowed_users.*.can_do_special' => ['nullable'],
        ]);

        $fixedFunds = $request->input('fixed_funds', []);
        $this->ensureFixedFundUsersCanReceive($fixedFunds);

        $cc = CostCenter::create([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
            'description' => $request->description,
            'menfis_email' => $request->menfis_email,
            'budget' => collect($fixedFunds)->sum('budget'),
            'beneficiary_id' => $fixedFunds[0]['user_id'] ?? null,
        ]);

        $this->syncFixedFunds($cc, $fixedFunds);

        if ($fixedFunds !== []) {
            // Create the initial budget record only when a fund was configured.
            $cc->budgetRenewals()->create([
                'amount' => collect($fixedFunds)->sum('budget'),
                'description' => 'Presupuesto inicial',
                'renewal_date' => now(),
                'user_id' => Auth::id(),
            ]);
        }

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
        $costCenter->load(['approvalSteps.user', 'fixedFunds.user']);
        $activeFixedFundReimbursementCounts = $costCenter->fixedFunds
            ->mapWithKeys(fn ($fund) => [
                $fund->id => $this->activeFixedFundReimbursements($fund)->count(),
            ]);

        return view('cost_centers.edit', compact('costCenter', 'users', 'companies', 'activeFixedFundReimbursementCounts'));
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
            'fixed_funds' => ['nullable', 'array'],
            'fixed_funds.*.id' => [
                'nullable', 'integer',
                Rule::exists('fixed_funds', 'id')->where(fn ($query) => $query->where('cost_center_id', $costCenter->id)),
            ],
            'fixed_funds.*.user_id' => ['required', 'exists:users,id'],
            'fixed_funds.*.name' => ['required', 'string', 'max:255'],
            'fixed_funds.*.budget' => ['required', 'numeric', 'min:0'],
            'fund_transfers' => ['nullable', 'array'],
            'fund_transfers.*.fund_id' => [
                'required',
                'integer',
                Rule::exists('fixed_funds', 'id')->where(fn ($query) => $query->where('cost_center_id', $costCenter->id)),
            ],
            'fund_transfers.*.transfer_to_user_id' => ['required', 'integer', 'exists:users,id'],
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

        $this->ensureFixedFundUsersCanReceive($request->input('fixed_funds', []));

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

            $fixedFunds = $request->input('fixed_funds', []);

            // 2. Update CC Basic Info
            $costCenter->update([
                'name' => $request->name,
                'company_id' => $request->company_id,
                'code' => strtoupper(\Illuminate\Support\Str::slug($request->name)),
                'description' => $request->description,
                'menfis_email' => $request->menfis_email,
                'budget' => collect($fixedFunds)->sum('budget'),
                'beneficiary_id' => $fixedFunds[0]['user_id'] ?? null,
            ]);

            $this->syncFixedFunds($costCenter, $fixedFunds, $request->input('fund_transfers', []));

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
            'fixed_fund_id' => [
                'required',
                Rule::exists('fixed_funds', 'id')->where(fn ($query) => $query->where('cost_center_id', $costCenter->id)->where('is_active', true)),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'renewal_date' => ['required', 'date'],
        ]);

        DB::transaction(function() use ($request, $costCenter) {
            $fixedFund = $costCenter->fixedFunds()->whereKey($request->fixed_fund_id)->lockForUpdate()->firstOrFail();
            // Create renewal record
            $costCenter->budgetRenewals()->create([
                'amount' => $request->amount,
                'description' => '[' . $fixedFund->name . '] ' . ($request->description ?: 'Renovación de fondo fijo'),
                'renewal_date' => $request->renewal_date,
                'user_id' => Auth::id(),
            ]);

            // Update total budget
            $fixedFund->increment('budget', $request->amount);
            $costCenter->update(['budget' => $costCenter->fixedFunds()->where('is_active', true)->sum('budget')]);
        });

        return redirect()->back()->with('success', 'Presupuesto renovado correctamente.');
    }
}
