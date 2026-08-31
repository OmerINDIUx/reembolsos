<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceLogin;
use App\Models\AccountBlockEvent;
use App\Models\ApprovalStep;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\ReimbursementApproval;
use App\Models\User;
use App\Services\AccountBlockService;
use App\Support\AccountBlockReasons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceAuditController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->integer('days', 30);
        $days = in_array($days, [7, 15, 30, 60, 90], true) ? $days : 30;
        $since = now()->subDays($days);
        $search = trim((string) $request->input('search'));

        $sharedDevices = DeviceLogin::query()
            ->select('device_hash')
            ->selectRaw('COUNT(*) as login_count')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->selectRaw('MAX(last_seen_at) as latest_activity')
            ->selectRaw('MAX(risk_score) as max_risk_score')
            ->where('last_seen_at', '>=', $since)
            ->groupBy('device_hash')
            ->havingRaw('COUNT(DISTINCT user_id) > 1')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereExists(function ($subQuery) use ($search) {
                    $subQuery->select(DB::raw(1))
                        ->from('device_logins as dl_search')
                        ->join('users as u_search', 'u_search.id', '=', 'dl_search.user_id')
                        ->whereColumn('dl_search.device_hash', 'device_logins.device_hash')
                        ->where(function ($innerQuery) use ($search) {
                            $innerQuery->where('u_search.name', 'like', "%{$search}%")
                                ->orWhere('u_search.email', 'like', "%{$search}%")
                                ->orWhere('dl_search.ip_address', 'like', "%{$search}%")
                                ->orWhere('dl_search.device_label', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('latest_activity')
            ->paginate(12, ['*'], 'shared_page')
            ->through(function ($device) use ($since) {
                $logins = DeviceLogin::with('user:id,name,email')
                    ->where('device_hash', $device->device_hash)
                    ->where('last_seen_at', '>=', $since)
                    ->latest('last_seen_at')
                    ->get();

                $device->users = $logins->unique('user_id')->pluck('user')->filter()->values();
                $device->label = $logins->first()?->device_label;
                $device->ip_addresses = $logins->pluck('ip_address')->filter()->unique()->take(3)->values();
                $device->locations = $logins->pluck('approx_location')->filter()->unique()->take(3)->values();

                return $device;
            });

        $riskUsers = DeviceLogin::query()
            ->join('users', 'users.id', '=', 'device_logins.user_id')
            ->select('users.id', 'users.name', 'users.email', 'users.blocked_at', 'users.blocked_reason_message')
            ->selectRaw('COUNT(DISTINCT device_logins.device_hash) as device_count')
            ->selectRaw('COUNT(device_logins.id) as login_count')
            ->selectRaw('MAX(device_logins.risk_score) as max_risk_score')
            ->selectRaw('SUM(CASE WHEN device_logins.is_new_device = 1 THEN 1 ELSE 0 END) as new_device_count')
            ->selectRaw('SUM(CASE WHEN device_logins.simultaneous_devices_count > 0 THEN 1 ELSE 0 END) as simultaneous_count')
            ->selectRaw('MAX(device_logins.shared_accounts_count) as shared_accounts_count')
            ->selectRaw('MAX(device_logins.last_seen_at) as latest_activity')
            ->where('device_logins.last_seen_at', '>=', $since)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('device_logins.ip_address', 'like', "%{$search}%")
                        ->orWhere('device_logins.device_label', 'like', "%{$search}%");
                });
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'users.blocked_at', 'users.blocked_reason_message')
            ->havingRaw('MAX(device_logins.risk_score) >= 30 OR COUNT(DISTINCT device_logins.device_hash) >= 3 OR SUM(CASE WHEN device_logins.simultaneous_devices_count > 0 THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('max_risk_score')
            ->orderByDesc('shared_accounts_count')
            ->orderByDesc('simultaneous_count')
            ->orderByDesc('device_count')
            ->orderByDesc('latest_activity')
            ->paginate(15, ['*'], 'risk_page');

        $simultaneousLogins = DeviceLogin::with('user:id,name,email,blocked_at')
            ->where('last_seen_at', '>=', $since)
            ->where('simultaneous_devices_count', '>', 0)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_label', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('logged_in_at')
            ->paginate(15, ['*'], 'simultaneous_page');

        $newDeviceLogins = DeviceLogin::with('user:id,name,email,blocked_at')
            ->where('last_seen_at', '>=', $since)
            ->where('is_new_device', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_label', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('logged_in_at')
            ->paginate(15, ['*'], 'new_device_page');

        $knownDevices = DeviceLogin::query()
            ->join('users', 'users.id', '=', 'device_logins.user_id')
            ->select('users.id as user_id', 'users.name', 'users.email', 'device_logins.device_hash')
            ->selectRaw('MAX(device_logins.device_label) as device_label')
            ->selectRaw('MAX(device_logins.ip_address) as last_ip_address')
            ->selectRaw('MAX(device_logins.approx_location) as approx_location')
            ->selectRaw('MAX(device_logins.last_seen_at) as latest_activity')
            ->selectRaw('COUNT(device_logins.id) as login_count')
            ->selectRaw('MAX(device_logins.risk_score) as max_risk_score')
            ->where('device_logins.last_seen_at', '>=', $since)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('device_logins.ip_address', 'like', "%{$search}%")
                        ->orWhere('device_logins.device_label', 'like', "%{$search}%");
                });
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'device_logins.device_hash')
            ->orderByDesc('latest_activity')
            ->paginate(25, ['*'], 'known_page');

        $recentLogins = DeviceLogin::with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_label', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('logged_in_at')
            ->paginate(25, ['*'], 'recent_page');

        $users = User::with('blockedByUser:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('blocked_at IS NULL')
            ->orderBy('name')
            ->paginate(20, ['*'], 'users_page');

        $blockEvents = AccountBlockEvent::with([
            'user:id,name,email',
            'actor:id,name',
        ])->latest()->limit(15)->get();

        $blockReasons = AccountBlockReasons::all();

        $summary = [
            'logins_today' => DeviceLogin::where('logged_in_at', '>=', today())->count(),
            'active_devices' => DeviceLogin::where('last_seen_at', '>=', $since)->distinct()->count('device_hash'),
            'shared_devices' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->select('device_hash')
                ->groupBy('device_hash')
                ->havingRaw('COUNT(DISTINCT user_id) > 1')
                ->get()
                ->count(),
            'risk_users' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->where('risk_score', '>=', 30)
                ->distinct()
                ->count('user_id'),
            'simultaneous_logins' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->where('simultaneous_devices_count', '>', 0)
                ->count(),
            'new_devices' => DeviceLogin::where('last_seen_at', '>=', $since)
                ->where('is_new_device', true)
                ->count(),
        ];

        return view('admin.device-audit.index', compact(
            'days',
            'search',
            'sharedDevices',
            'riskUsers',
            'simultaneousLogins',
            'newDeviceLogins',
            'knownDevices',
            'recentLogins',
            'summary',
            'users',
            'blockEvents',
            'blockReasons'
        ));
    }

    public function exportUsers(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $users = User::with(['profile', 'authorizedCostCenters:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $fileName = 'usuarios_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nombre', 'Correo', 'Puesto', 'Centro(s) de costos']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->profile?->display_name ?: $user->role_name,
                    $user->authorizedCostCenters->pluck('name')->implode(' | '),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Export the configured approval flow, one row per cost-center step. */
    public function exportApproverMatrix()
    {
        $steps = ApprovalStep::query()
            ->with(['costCenter.company', 'user.profile'])
            ->orderBy('cost_center_id')
            ->orderBy('order')
            ->get();

        return response()->streamDownload(function () use ($steps) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Centro de costos', 'Clave', 'Empresa', 'Orden', 'Etapa de aprobación', 'Aprobador', 'Correo', 'Puesto', 'Estado']);

            foreach ($steps as $step) {
                fputcsv($handle, [
                    $step->costCenter?->name ?: 'Sin centro de costos',
                    $step->costCenter?->code ?: '',
                    $step->costCenter?->company?->name ?: 'Sin empresa',
                    $step->order,
                    $step->name ?: 'Sin nombre',
                    $step->user?->name ?: 'Sin asignar',
                    $step->user?->email ?: '',
                    $step->user?->profile?->display_name ?: ($step->user?->role_name ?: ''),
                    $step->costCenter?->is_active ? 'Activo' : 'Inactivo',
                ]);
            }

            fclose($handle);
        }, 'matriz_aprobadores_centros_costos_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Live replacement for dashboard_reembolsos_2.html. */
    public function reimbursementsDashboard(Request $request): View
    {
        $range = $request->input('range', '90');
        $range = in_array($range, ['30', '90', '180', '365', 'all'], true) ? $range : '90';
        $companyId = $request->integer('company');
        $costCenterId = $request->integer('cost_center');
        $from = $range === 'all' ? null : now()->subDays((int) $range)->startOfDay();

        $baseQuery = Reimbursement::query()
            ->with([
                'user:id,name', 'costCenter:id,name,code,company_id,budget,is_active', 'costCenter.company:id,name',
                'cxpApprover:id,name', 'treasuryApprover:id,name', 'fixedFund:id,name,budget,is_active',
            ])
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($companyId, fn ($query) => $query->whereHas('costCenter', fn ($centers) => $centers->where('company_id', $companyId)))
            ->when($costCenterId, fn ($query) => $query->where('cost_center_id', $costCenterId));

        $reimbursements = $baseQuery->latest('created_at')->get();
        $totalAmount = (float) $reimbursements->sum('total');
        $paid = $reimbursements->filter(fn ($item) => $item->approved_by_treasury_at !== null);
        $pendingStatuses = ['pendiente', 'aprobado_director', 'aprobado_ejecutivo', 'pendiente_revision_cxp', 'pendiente_pago', 'requiere_correccion'];
        $pending = $reimbursements->whereIn('status', $pendingStatuses);

        $statusBreakdown = $reimbursements->groupBy('status')->map(fn ($items, $status) => [
            'status' => $status,
            'count' => $items->count(),
            'amount' => (float) $items->sum('total'),
        ])->sortByDesc('amount')->values();

        $monthlyTrend = $reimbursements->groupBy(fn ($item) => $item->created_at->format('Y-m'))
            ->map(fn ($items, $month) => [
                'month' => $month,
                'count' => $items->count(),
                'amount' => (float) $items->sum('total'),
                'paid' => (float) $items->filter(fn ($item) => $item->approved_by_treasury_at !== null)->sum('total'),
            ])->sortBy('month')->values();

        $topCostCenters = $reimbursements->groupBy('cost_center_id')->map(function ($items) {
            $center = $items->first()->costCenter;
            return [
                'name' => $center?->name ?: 'Sin centro de costos',
                'code' => $center?->code ?: '—',
                'count' => $items->count(),
                'amount' => (float) $items->sum('total'),
                'pending_amount' => (float) $items->whereIn('status', ['pendiente', 'pendiente_revision_cxp', 'pendiente_pago'])->sum('total'),
            ];
        })->sortByDesc('amount')->take(10)->values();

        // Same duplicate signal used by the original dashboard: issuer RFC + amount + invoice date.
        $duplicates = $reimbursements->filter(fn ($item) => filled($item->rfc_emisor) && $item->total !== null && $item->fecha)
            ->groupBy(fn ($item) => implode('|', [$item->rfc_emisor, number_format((float) $item->total, 2, '.', ''), $item->fecha->toDateString()]))
            ->filter(fn ($items) => $items->count() > 1)
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'rfc' => $first->rfc_emisor,
                    'date' => $first->fecha->toDateString(),
                    'count' => $items->count(),
                    'amount' => (float) $items->sum('total'),
                    'ids' => $items->pluck('id')->implode(', '),
                ];
            })->sortByDesc('amount')->take(10)->values();

        $approvalEvents = ReimbursementApproval::query()
            ->whereIn('reimbursement_id', $reimbursements->pluck('id'))
            ->with('user:id,name')
            ->get()
            ->groupBy('reimbursement_id');

        $approvalMetrics = $approvalEvents
            ->flatten(1)
            ->filter(fn ($approval) => $approval->user)
            ->groupBy('user_id')
            ->map(function ($approvals) {
                $user = $approvals->first()->user;
                return [
                    'name' => $user->name,
                    'decisions' => $approvals->whereIn('action', ['aprobado', 'rechazado', 'requiere_correccion'])->count(),
                    'approved' => $approvals->where('action', 'aprobado')->count(),
                    'rejected' => $approvals->where('action', 'rechazado')->count(),
                    'corrections' => $approvals->where('action', 'requiere_correccion')->count(),
                    'bulk' => $approvals->where('is_bulk', true)->count(),
                ];
            })->sortByDesc('decisions')->take(12)->values();

        $processedStatuses = ['pendiente_revision_cxp', 'pendiente_pago', 'aprobado'];
        $analyticsRows = $reimbursements->map(function (Reimbursement $reimbursement) use ($approvalEvents, $processedStatuses) {
            $events = ($approvalEvents->get($reimbursement->id) ?? collect())->sortBy('created_at')->values();
            $submittedAt = $events->first(fn ($event) => in_array($event->action, ['enviado', 'resubmitted'], true))?->created_at ?? $reimbursement->created_at;
            $approvedEvents = $events->filter(fn ($event) => $event->action === 'aprobado')->values();
            $firstApprovalAt = $approvedEvents->first()?->created_at;
            $lastOperationalApprovalAt = $approvedEvents->filter(fn ($event) => !$reimbursement->approved_by_cxp_at || $event->created_at->lte($reimbursement->approved_by_cxp_at))->last()?->created_at;
            $total = (float) $reimbursement->total + (float) ($reimbursement->propina ?? 0);
            $isProcessed = in_array($reimbursement->status, $processedStatuses, true);
            $rules = [];

            if ($isProcessed && $total > 2000 && blank($reimbursement->uuid)) $rules[] = 'R1';
            if ($isProcessed && $total > 2000 && str_contains(strtolower((string) $reimbursement->forma_pago), 'efectivo')) $rules[] = 'R3';
            if ($isProcessed && str_contains(strtolower((string) $reimbursement->category), 'medic')) $rules[] = 'R6';
            if ($isProcessed && $reimbursement->type === 'fondo_fijo' && ($total < 1 || $total > 2000)) $rules[] = 'R2';
            if ($isProcessed && in_array($reimbursement->type, ['hospedaje', 'viaticos'], true) && !$reimbursement->travel_event_id) $rules[] = 'R7';
            if ($isProcessed && $reimbursement->type === 'comida' && (int) $reimbursement->attendees_count > 0 && $total / (int) $reimbursement->attendees_count > 650) $rules[] = 'R4';
            if ($isProcessed && str_contains(strtolower((string) $reimbursement->category), 'telefon')) $rules[] = 'R6b';
            if ($isProcessed && (float) ($reimbursement->propina ?? 0) > (float) $reimbursement->total * .15) $rules[] = 'R5';
            if ($isProcessed && $reimbursement->fecha && !$reimbursement->fecha->isSameMonth($submittedAt)) $rules[] = 'R8';

            return [
                'model' => $reimbursement,
                'total' => $total,
                'submitted_at' => $submittedAt,
                'first_approval_at' => $firstApprovalAt,
                'last_operational_approval_at' => $lastOperationalApprovalAt,
                'cxp_at' => $reimbursement->approved_by_cxp_at,
                'paid_at' => $reimbursement->approved_by_treasury_at,
                'rules' => $rules,
                'events' => $events,
            ];
        });

        $durationValues = function (string $from, string $to) use ($analyticsRows) {
            return $analyticsRows->filter(fn ($row) => $row[$from] && $row[$to] && $row[$to]->gte($row[$from]))
                ->map(fn ($row) => $row[$from]->diffInMinutes($row[$to]) / 1440)->values();
        };
        $median = function ($values) {
            $values = $values->sort()->values();
            $count = $values->count();
            if (!$count) return null;
            $middle = intdiv($count, 2);
            return $count % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
        };
        $stageDurations = [
            'first_approval' => $median($durationValues('submitted_at', 'first_approval_at')),
            'approval_chain' => $median($durationValues('first_approval_at', 'last_operational_approval_at')),
            'cxp_wait' => $median($durationValues('last_operational_approval_at', 'cxp_at')),
            'payment' => $median($durationValues('cxp_at', 'paid_at')),
            'total_cycle' => $median($durationValues('submitted_at', 'paid_at')),
        ];

        $cxpBacklog = $analyticsRows->filter(fn ($row) => $row['model']->status === 'pendiente_revision_cxp');
        $unpaid = $analyticsRows->filter(fn ($row) => $row['model']->status === 'pendiente_pago' && !$row['paid_at']);
        $cxpThroughput = $analyticsRows->flatMap(function ($row) {
            $items = [];
            if ($row['cxp_at']) $items[] = ['week' => $row['cxp_at']->format('o-\\WW'), 'reviewed' => 1, 'paid' => 0];
            if ($row['paid_at']) $items[] = ['week' => $row['paid_at']->format('o-\\WW'), 'reviewed' => 0, 'paid' => 1];
            return $items;
        })->groupBy('week')->map(fn ($items, $week) => ['week' => $week, 'reviewed' => $items->sum('reviewed'), 'paid' => $items->sum('paid')])->sortBy('week')->values();
        $cxpWorkload = $analyticsRows->flatMap(function ($row) {
            $items = [];
            if ($row['cxp_at'] && $row['model']->cxpApprover) $items[] = ['name' => $row['model']->cxpApprover->name, 'reviewed' => 1, 'paid' => 0];
            if ($row['paid_at'] && $row['model']->treasuryApprover) $items[] = ['name' => $row['model']->treasuryApprover->name, 'reviewed' => 0, 'paid' => 1];
            return $items;
        })->groupBy('name')->map(fn ($items, $name) => ['name' => $name, 'reviewed' => $items->sum('reviewed'), 'paid' => $items->sum('paid'), 'total' => $items->count()])->sortByDesc('total')->take(12)->values();
        $complianceRules = [
            'R1' => ['label' => 'Gasto mayor a $2,000 sin CFDI', 'severity' => 'critical'],
            'R3' => ['label' => 'Más de $2,000 pagado en efectivo', 'severity' => 'critical'],
            'R6' => ['label' => 'Categoría excluida: medicinas', 'severity' => 'critical'],
            'R2' => ['label' => 'Fondo fijo fuera de rango', 'severity' => 'serious'],
            'R7' => ['label' => 'Hospedaje o viáticos sin viaje', 'severity' => 'serious'],
            'R4' => ['label' => 'Comida mayor a $650 por persona', 'severity' => 'serious'],
            'R6b' => ['label' => 'Rubro fuera del sistema: telefonía', 'severity' => 'warning'],
            'R5' => ['label' => 'Propina mayor al 15%', 'severity' => 'warning'],
            'R8' => ['label' => 'Solicitado fuera del mes del gasto', 'severity' => 'warning'],
        ];

        $approvalMatrix = CostCenter::query()->with(['company:id,name', 'approvalSteps.user:id,name,email'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($costCenterId, fn ($query) => $query->whereKey($costCenterId))
            ->orderBy('name')->get();

        return view('admin.device-audit.reimbursements-dashboard', compact(
            'range', 'companyId', 'costCenterId', 'reimbursements', 'totalAmount', 'paid', 'pending',
            'statusBreakdown', 'monthlyTrend', 'topCostCenters', 'duplicates', 'approvalMetrics', 'approvalMatrix',
            'analyticsRows', 'stageDurations', 'cxpBacklog', 'unpaid', 'cxpThroughput', 'cxpWorkload', 'complianceRules',
        ) + [
            'companies' => \App\Models\Company::orderBy('name')->get(['id', 'name']),
            'costCenters' => CostCenter::orderBy('name')->get(['id', 'name', 'code', 'company_id']),
        ]);
    }

    public function block(Request $request, User $user, AccountBlockService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(AccountBlockReasons::all()))],
        ]);

        $service->block($user, $request->user(), $validated['reason'], $request);

        return back()->with('success', "La cuenta de {$user->name} fue bloqueada y sus sesiones fueron cerradas.");
    }

    public function unblock(Request $request, User $user, AccountBlockService $service): RedirectResponse
    {
        $service->unblock($user, $request->user(), $request);

        return back()->with('success', "La cuenta de {$user->name} fue desbloqueada.");
    }
}
