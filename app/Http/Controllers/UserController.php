<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\UserInvitation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;
use App\Models\Permission;
use App\Models\CostCenter;
use App\Services\AccountBlockService;

class UserController extends Controller
{
    private const ALLOWED_EMAIL_DOMAINS = [
        '@grupoindi.com',
        '@construlerma.com',
        '@archandel.com',
    ];


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['director', 'profile'])
            ->withCount([
                'fixedFunds as active_fixed_funds_count' => fn ($funds) => $funds->where('is_active', true),
                'approvalSteps',
                'reimbursements as active_reimbursements_count' => fn ($reimbursements) => $reimbursements->whereIn('status', ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo']),
            ])
            ->orderBy('name');

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
            $status = $request->status === 'inactive' ? 'pending' : ($request->status === 'blocked' ? 'disabled' : $request->status);
            $query->where('status', $status);
        }

        $users = $query->paginate(10)->appends($request->all());
        $fixedFundTransferCandidates = User::with('profile')
            ->whereNull('invitation_token')
            ->whereNull('blocked_at')
            ->where(function ($candidate) {
                $candidate->where('role', '!=', 'tesoreria')
                    ->whereDoesntHave('profile', fn ($profile) => $profile->where('name', 'tesoreria'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'profile_id']);

        $activeUserCandidates = User::with('profile')
            ->where('id', '!=', $request->user()->id)
            ->whereNull('invitation_token')
            ->whereNull('blocked_at')
            ->orderBy('name')
            ->get(['id', 'name', 'profile_id']);

        return view('users.index', compact('users', 'fixedFundTransferCandidates', 'activeUserCandidates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $profiles = $this->availableProfilesFor(Auth::user());

        return view('users.create', compact('profiles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $email = User::normalizeEmail($request->input('email'));
        $request->merge(['email' => $email]);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->corporateEmailDomainRule(), 'unique:users,email_normalized'],
            'profile_id' => ['required', 'exists:profiles,id'],
        ]);

        $profile = Profile::findOrFail($request->profile_id);
        if ($this->isAdminProfile($profile) && !Auth::user()->isAdmin()) {
            return back()->withInput()->with('error', 'Solo un administrador puede crear otro usuario administrador.');
        }

        $user = DB::transaction(function () use ($request, $profile, $email) {
            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'email_normalized' => $email,
                'password' => null,
                'role' => in_array($profile->name, ['admin', 'admin_view', 'director', 'control_obra', 'director_ejecutivo', 'accountant', 'direccion', 'tesoreria', 'user']) ? $profile->name : 'user',
                'profile_id' => $profile->id,
                'invitation_token' => Str::random(64),
                'status' => 'pending',
            ]);
            return $user;
        });

        if (!$this->sendInvitationEmail($user)) {
            return redirect()->route('users.index')->with('error', 'El usuario fue creado, pero no se pudo enviar la invitación.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado como pendiente. Debe iniciar sesión con el proveedor correspondiente a su dominio.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $periods = \App\Models\Reimbursement::getAvailableTimePeriods();
        $user->load(['director', 'subordinates', 'costCenters', 'substitutes.user']);

        // Centros donde el usuario participa, ya sea como solicitante, aprobador,
        // responsable de fondo fijo o dentro de alguno de los puestos del flujo.
        $costCenterAssignments = CostCenter::query()
            ->where(function ($query) use ($user) {
                $query->where('director_id', $user->id)
                    ->orWhere('control_obra_id', $user->id)
                    ->orWhere('director_ejecutivo_id', $user->id)
                    ->orWhere('accountant_id', $user->id)
                    ->orWhere('direccion_id', $user->id)
                    ->orWhere('tesoreria_id', $user->id)
                    ->orWhere('beneficiary_id', $user->id)
                    ->orWhereHas('approvalSteps', fn ($steps) => $steps->where('user_id', $user->id))
                    ->orWhereHas('authorizedUsers', fn ($users) => $users->where('users.id', $user->id))
                    ->orWhereHas('fixedFunds', fn ($funds) => $funds
                        ->where('user_id', $user->id)
                        ->where('is_active', true));
            })
            ->with([
                'approvalSteps' => fn ($steps) => $steps->where('user_id', $user->id),
                'authorizedUsers' => fn ($users) => $users->where('users.id', $user->id),
                'fixedFunds' => fn ($funds) => $funds
                    ->where('user_id', $user->id)
                    ->where('is_active', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($costCenter) use ($user) {
                $positions = collect([
                    $costCenter->director_id === $user->id ? 'Director N1' : null,
                    $costCenter->control_obra_id === $user->id ? 'Control de Obra N2' : null,
                    $costCenter->director_ejecutivo_id === $user->id ? 'Director Ejecutivo N3' : null,
                    $costCenter->accountant_id === $user->id ? 'Cuentas por Pagar Revisador' : null,
                    $costCenter->direccion_id === $user->id ? 'Subdirección N5' : null,
                    $costCenter->tesoreria_id === $user->id ? 'Cuentas por Pagar Pagador' : null,
                    $costCenter->beneficiary_id === $user->id ? 'Beneficiario' : null,
                    $costCenter->authorizedUsers->isNotEmpty()
                        ? ($costCenter->authorizedUsers->first()->pivot->can_do_special
                            ? 'Usuario autorizado (con permisos especiales)'
                            : 'Usuario autorizado')
                        : null,
                ])->filter();

                $costCenter->approvalSteps->each(function ($step) use ($positions) {
                    $positions->push($step->name ?: 'Aprobador N' . $step->order);
                });

                $costCenter->fixedFunds->each(function ($fund) use ($positions) {
                    $positions->push('Responsable de fondo fijo' . ($fund->name ? ': ' . $fund->name : ''));
                });

                return [
                    'costCenter' => $costCenter,
                    'positions' => $positions->unique()->values(),
                ];
            });

        // 1. Personal Spending Stats
        $pendingQuery = $user->reimbursements()->applyTimeFilters($request)->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);
        $approvedQuery = $user->reimbursements()->applyTimeFilters($request)->where('status', 'aprobado');

        $stats = [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'approved_count' => (clone $approvedQuery)->count(),
            'approved_amount' => (clone $approvedQuery)->sum(DB::raw('total + COALESCE(propina, 0)')),
            'rejected_count' => $user->reimbursements()->applyTimeFilters($request)->where('status', 'rechazado')->count(),
        ];

        // 2. Category Breakdown (Personal)
        $categoryBreakdown = $user->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select('category', DB::raw('sum(total + COALESCE(propina, 0)) as amount'), DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->get();

        // 3. Status Breakdown
        $statusBreakdown = $user->reimbursements()
            ->applyTimeFilters($request)
            ->where('status', '!=', 'borrador')
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->groupBy('status')
            ->get();

        // 4. Monthly Trend (Last 6 months)
        $monthlyTrend = $user->reimbursements()
            ->where('status', 'aprobado')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('sum(total + COALESCE(propina, 0)) as amount')
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
        $pendingApprovalsCount = \App\Models\Reimbursement::whereHas('currentStep', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->count();

        // 7. Substitutes
        $allUsers = User::where('id', '!=', $user->id)->orderBy('name')->get();

        return view('users.show', compact('user', 'stats', 'categoryBreakdown', 'statusBreakdown', 'monthlyTrend', 'recentReimbursements', 'pendingApprovalsCount', 'periods', 'allUsers', 'costCenterAssignments'));
    }

    /**
     * Add a substitute for the user.
     */
    public function addSubstitute(Request $request, User $user)
    {
        $this->ensureCanManageUser($user, 'editar');

        $request->validate([
            'substitute_id' => 'required|exists:users,id|different:' . $user->id,
        ]);

        \App\Models\UserSubstitute::updateOrCreate(
            [
                'original_user_id' => $user->id,
                'user_id' => $request->substitute_id
            ],
            ['is_active' => true]
        );

        return back()->with('success', 'Sustituto asignado correctamente.');
    }

    /**
     * Toggle substitute status.
     */
    public function toggleSubstitute(User $user, $substituteId)
    {
        $this->ensureCanManageUser($user, 'editar');

        $sub = \App\Models\UserSubstitute::where('original_user_id', $user->id)
            ->where('user_id', $substituteId)
            ->firstOrFail();
            
        $sub->is_active = !$sub->is_active;
        $sub->save();

        return back()->with('success', 'Estado de la sustitución actualizado.');
    }

    /**
     * Remove a substitute.
     */
    public function removeSubstitute(User $user, $substituteId)
    {
        $this->ensureCanManageUser($user, 'editar');

        \App\Models\UserSubstitute::where('original_user_id', $user->id)
            ->where('user_id', $substituteId)
            ->delete();

        return back()->with('success', 'Sustitución eliminada.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        if ($this->isAdminUser($user) && !Auth::user()->isAdmin()) {
            abort(403, 'Solo un administrador puede editar usuarios administradores.');
        }

        $directors = User::whereIn('role', ['admin', 'director'])->where('id', '!=', $user->id)->get();
        $profiles = $this->availableProfilesFor(Auth::user(), $user->profile_id);
        $costCenters = CostCenter::active()->orderBy('name')->get(['id', 'code', 'name']);
        $permissions = Permission::orderBy('module')->orderBy('display_name')->get();
        $user->loadMissing(['authorizedCostCenters:id', 'permissions:id']);

        return view('users.edit', compact('user', 'directors', 'profiles', 'costCenters', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $email = User::normalizeEmail($request->input('email'));
        $this->ensureCanManageUser($user, 'editar');

        $request->merge(['email' => $email]);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->corporateEmailDomainRule(), Rule::unique('users', 'email_normalized')->ignore($user->id)],
            'profile_id' => ['required', 'exists:profiles,id'],
            'status' => ['required', Rule::in(['pending', 'active', 'disabled'])],
            'cost_centers' => ['nullable', 'array'],
            'cost_centers.*' => ['integer', 'exists:cost_centers,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'clabe' => ['nullable', 'string', 'size:18', 'regex:/^[0-9]+$/'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13'],
        ]);

        $profile = Profile::findOrFail($request->profile_id);
        if ($this->isAdminProfile($profile) && !Auth::user()->isAdmin()) abort(403, 'Solo un administrador puede asignar un perfil administrador.');

        DB::transaction(function () use ($request, $user, $profile, $email) {
            $user->update([
                'name' => $request->name,
                'email' => $email,
                'email_normalized' => $email,
                'status' => $request->status,
                'role' => in_array($profile->name, ['admin', 'admin_view', 'director', 'control_obra', 'director_ejecutivo', 'accountant', 'direccion', 'tesoreria', 'user']) ? $profile->name : 'user',
                'profile_id' => $profile->id,
                'bank_name' => $request->filled('bank_name') ? strtoupper(trim($request->bank_name)) : null,
                'clabe' => $request->clabe,
                'rfc' => $request->filled('rfc') ? strtoupper(trim($request->rfc)) : null,
                'blocked_at' => $request->status === 'disabled' ? ($user->blocked_at ?: now()) : null,
            ]);
            $user->authorizedCostCenters()->sync($request->input('cost_centers', []));
            $user->permissions()->sync($request->input('permissions', []));
        });

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Show the two-step deactivation screen with individual operation decisions.
     */
    public function deactivation(User $user)
    {
        if ($user->getKey() === Auth::id()) return redirect()->route('users.index')->with('error', 'No puedes inhabilitar tu propia cuenta.');
        $this->ensureCanManageUser($user, 'inhabilitar');

        if ($user->isBlocked()) return redirect()->route('users.index')->with('error', 'Esta cuenta ya se encuentra inhabilitada.');
        $pendingStatuses = ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo'];
        $pendingReimbursements = $user->reimbursements()->whereIn('status', $pendingStatuses)->with(['costCenter', 'user', 'payee'])->orderBy('cost_center_id')->orderBy('id')->get();
        $approvalSteps = $user->approvalSteps()->with('costCenter')->orderBy('cost_center_id')->orderBy('order')->get();
        $activeFunds = $user->fixedFunds()->where('is_active', true)->with('costCenter')->orderBy('cost_center_id')->orderBy('id')->get();
        $activeUserCandidates = User::with('profile')->where('id', '!=', $user->id)->whereNull('invitation_token')->whereNull('blocked_at')->orderBy('name')->get(['id', 'name', 'profile_id']);
        $activeReimbursementsCount = $pendingReimbursements->count();
        $approvalStepsCount = $approvalSteps->count();
        $activeFixedFundsCount = $activeFunds->count();
        return view('users.deactivation', compact('user', 'pendingReimbursements', 'approvalSteps', 'activeFunds', 'activeReimbursementsCount', 'approvalStepsCount', 'activeFixedFundsCount', 'activeUserCandidates'));
    }    public function destroy(Request $request, User $user, AccountBlockService $blockService)
    {
        if ($user->getKey() === Auth::id()) return back()->with('error', 'No puedes inhabilitar tu propia cuenta.');
        $this->ensureCanManageUser($user, 'inhabilitar');

        if ($user->isBlocked()) return back()->with('error', 'Esta cuenta ya se encuentra inhabilitada.');

        $request->validate([
            'reimbursement_action' => ['required', Rule::in(['keep', 'transfer'])],
            'reimbursement_transfer_to_user_id' => ['nullable', 'integer', 'different:' . $user->id, 'exists:users,id'],
            'approval_action' => ['required', Rule::in(['reassign', 'remove'])],
            'approval_reassign_to_user_id' => ['nullable', 'integer', 'different:' . $user->id, 'exists:users,id'],
            'pending_approval_action' => ['required', Rule::in(['keep', 'previous', 'next'])],
            'transfer_to_user_id' => ['nullable', 'integer', 'different:' . $user->id, 'exists:users,id'],
            'reimbursement_decisions' => ['nullable', 'array'],
            'reimbursement_decisions.*' => [Rule::in(['keep', 'transfer'])],
            'reimbursement_transfer_to_user_ids' => ['nullable', 'array'],
            'reimbursement_transfer_to_user_ids.*' => ['nullable', 'integer', 'different:' . $user->id, 'exists:users,id'],
            'fixed_fund_decisions' => ['nullable', 'array'],
            'fixed_fund_decisions.*' => [Rule::in(['delete', 'transfer'])],
            'fixed_fund_transfer_to_user_ids' => ['nullable', 'array'],
            'fixed_fund_transfer_to_user_ids.*' => ['nullable', 'integer', 'different:' . $user->id, 'exists:users,id'],
        ]);

        $replacementIds = collect([
            $request->integer('reimbursement_transfer_to_user_id'),
            $request->integer('approval_reassign_to_user_id'),
            $request->integer('transfer_to_user_id'),
        ])->merge($request->input('reimbursement_transfer_to_user_ids', []))->merge($request->input('fixed_fund_transfer_to_user_ids', []))->filter()->unique();

        if (User::whereIn('id', $replacementIds)->where(function ($query) {
            $query->whereNotNull('blocked_at')->orWhereNotNull('invitation_token');
        })->exists()) {
            return back()->with('error', 'Los responsables seleccionados deben ser usuarios activos y registrados.');
        }

        $activeFunds = \App\Models\FixedFund::where('user_id', $user->id)->where('is_active', true)->get();
        if ($activeFunds->isNotEmpty() && !$request->filled('transfer_to_user_id') && !$request->filled('fixed_fund_decisions')) {
            return back()->with('error', 'Selecciona quién recibirá los fondos fijos antes de inhabilitar al usuario.');
        }
        $fundReplacement = $activeFunds->isNotEmpty() && $request->filled('transfer_to_user_id') ? User::with('profile')->findOrFail($request->integer('transfer_to_user_id')) : null;
        if ($fundReplacement?->hasRole('tesoreria')) {
            return back()->with('error', 'Cuentas por Pagar Pagadores no puede recibir la asignación de un fondo fijo.');
        }

        $pendingStatuses = ['pendiente', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo'];
        $ownedReimbursements = \App\Models\Reimbursement::where('user_id', $user->id)->whereIn('status', $pendingStatuses)->get();
        $approvalSteps = \App\Models\ApprovalStep::where('user_id', $user->id)->get();
        $linkedCostCenterIds = \App\Models\CostCenter::where(function ($query) use ($user) {
            foreach (['director_id', 'control_obra_id', 'director_ejecutivo_id', 'accountant_id', 'direccion_id', 'tesoreria_id', 'beneficiary_id'] as $field) {
                $query->orWhere($field, $user->id);
            }
        })->orWhereHas('authorizedUsers', fn ($query) => $query->where('users.id', $user->id))
            ->pluck('id')
            ->concat($approvalSteps->pluck('cost_center_id'))
            ->unique()
            ->values();

        if ($ownedReimbursements->isNotEmpty() && $request->input('reimbursement_action') === 'transfer' && !$request->filled('reimbursement_transfer_to_user_id')) {
            return back()->with('error', 'Selecciona quién recibirá los reembolsos propios en proceso.');
        }
        if ($approvalSteps->isNotEmpty() && $request->input('approval_action') === 'reassign' && !$request->filled('approval_reassign_to_user_id')) {
            return back()->with('error', 'Selecciona quién sustituirá al aprobador en los centros de costos.');
        }

        DB::transaction(function () use ($request, $user, $blockService, $fundReplacement, $ownedReimbursements, $approvalSteps, $linkedCostCenterIds, $pendingStatuses) {
            foreach (\App\Models\FixedFund::where('user_id', $user->id)->where('is_active', true)->lockForUpdate()->get() as $fund) {
                $decision = $request->input('fixed_fund_decisions.' . $fund->id, $request->filled('transfer_to_user_id') ? 'transfer' : 'delete');
                if ($decision === 'delete') { $fund->update(['is_active' => false]); continue; }
                $targetId = $request->input('fixed_fund_transfer_to_user_ids.' . $fund->id, $request->integer('transfer_to_user_id'));
                if ($targetId) $fund->update(['user_id' => $targetId]);
            }

            foreach ($ownedReimbursements as $reimbursement) {
                $decision = $request->input('reimbursement_decisions.' . $reimbursement->id, $request->input('reimbursement_action', 'keep'));
                if ($decision !== 'transfer') continue;
                $targetId = $request->input('reimbursement_transfer_to_user_ids.' . $reimbursement->id, $request->integer('reimbursement_transfer_to_user_id'));
                if (!$targetId) continue;
                $data = ['user_id' => $targetId];
                if ((int) $reimbursement->payee_id === (int) $user->id) $data['payee_id'] = $data['user_id'];
                $reimbursement->update($data);
            }

            foreach ($linkedCostCenterIds as $costCenterId) {
                $userSteps = $approvalSteps->where('cost_center_id', $costCenterId);
                $costCenter = \App\Models\CostCenter::lockForUpdate()->find($costCenterId);
                if (!$costCenter) continue;
                $oldSteps = $costCenter->approvalSteps()->orderBy('order')->get();
                $current = $costCenter->reimbursements()->whereIn('status', $pendingStatuses)->whereIn('current_step_id', $userSteps->pluck('id'))->get();

                if ($request->input('approval_action') === 'reassign') {
                    $costCenter->approvalSteps()->where('user_id', $user->id)->update(['user_id' => $request->integer('approval_reassign_to_user_id')]);

                    if ($request->input('pending_approval_action') !== 'keep') {
                        foreach ($current as $reimbursement) {
                            $oldOrder = $oldSteps->firstWhere('id', $reimbursement->current_step_id)?->order;
                            $candidate = $request->input('pending_approval_action') === 'previous'
                                ? $oldSteps->where('order', '<', $oldOrder)->sortByDesc('order')->first()
                                : $oldSteps->where('order', '>', $oldOrder)->sortBy('order')->first();

                            $reimbursement->update([
                                'current_step_id' => $candidate?->id,
                                'status' => $candidate ? 'pendiente' : 'pendiente_revision_cxp',
                            ]);
                        }
                    }
                } else {
                    $costCenter->approvalSteps()->where('user_id', $user->id)->delete();
                    $remaining = $costCenter->approvalSteps()->orderBy('order')->get();
                    foreach ($remaining as $index => $step) $step->update(['order' => $index + 1]);
                    if ($remaining->isEmpty()) $costCenter->update(['is_active' => false]);

                    foreach ($current as $reimbursement) {
                        $oldOrder = $oldSteps->firstWhere('id', $reimbursement->current_step_id)?->order;
                        $candidate = $request->input('pending_approval_action') === 'previous'
                            ? $remaining->where('order', '<', $oldOrder)->sortByDesc('order')->first()
                            : $remaining->where('order', '>', $oldOrder)->sortBy('order')->first();
                        $reimbursement->update(['current_step_id' => $candidate?->id, 'status' => $candidate ? 'pendiente' : 'pendiente_revision_cxp']);
                    }
                }

                $legacyFields = ['director_id', 'control_obra_id', 'director_ejecutivo_id', 'accountant_id', 'direccion_id', 'tesoreria_id', 'beneficiary_id'];
                $updates = [];
                foreach ($legacyFields as $field) if ((int) $costCenter->{$field} === (int) $user->id) $updates[$field] = null;
                if ($updates) $costCenter->update($updates);
                $costCenter->authorizedUsers()->detach($user->id);
            }

            $blockService->block($user, Auth::user(), 'administrative_review', $request);
        });

        return redirect()->route('users.index')->with('success', 'Usuario inhabilitado correctamente. Se conservaron sus históricos y se aplicaron las decisiones seleccionadas.');
    }
    /**
     * Resend invitation to a user.
     */
    public function resendInvitation(User $user)
    {
        $this->ensureCanManageUser($user, 'reenviar la invitación de');

        if ($user->isRegistered()) {
            return back()->with('error', 'Este usuario ya ha completado su registro.');
        }

        if (!$user->invitation_token) {
            $user->update(['invitation_token' => Str::random(64)]);
        }

        if (!$this->sendInvitationEmail($user)) {
            return back()->with('error', 'No se pudo reenviar la invitación. Revisa la configuración de correo en el servidor.');
        }

        return back()->with('success', 'La invitación ha sido reenviada exitosamente.');
    }

    private function sendInvitationEmail(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new UserInvitation($user));
            $user->forceFill(['invitation_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando invitación de usuario.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function availableProfilesFor(User $actor, ?int $currentProfileId = null)
    {
        $query = Profile::orderBy('display_name');

        if (!$actor->isAdmin() && $currentProfileId) {
            $query->where(function ($profiles) use ($currentProfileId) {
                $profiles->whereNotIn('name', ['admin', 'admin_view'])
                    ->orWhere('id', $currentProfileId);
            });
        } elseif (!$actor->isAdmin()) {
            $query->whereNotIn('name', ['admin', 'admin_view']);
        }

        return $query->get();
    }

    private function isAdminProfile(Profile $profile): bool
    {
        return in_array($profile->name, ['admin', 'admin_view'], true);
    }

    private function isAdminUser(User $user): bool
    {
        return in_array($user->role, ['admin', 'admin_view'], true)
            || ($user->profile && $this->isAdminProfile($user->profile));
    }

    private function corporateEmailDomainRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $email = User::normalizeEmail($value);
            $domain = strrchr($email, '@') ?: '';

            if (!in_array($domain, self::ALLOWED_EMAIL_DOMAINS, true)) {
                $fail('El correo debe pertenecer a @grupoindi.com, @construlerma.com o @archandel.com.');
            }
        };
    }

    private function ensureCanManageUser(User $user, string $action): void
    {
        if ($this->isAdminUser($user) && !Auth::user()->isAdmin()) {
            abort(403, "Solo un administrador puede {$action} usuarios administradores.");
        }
    }
}
