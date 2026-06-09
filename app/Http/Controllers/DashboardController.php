<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->filled('period_type')) {
            $request->merge([
                'period_type' => 'month',
                'period_month' => now()->format('Y-m')
            ]);
        }

        $user = Auth::user();
        $periods = Reimbursement::getAvailableTimePeriods();

        $stats = [];
        $recentReimbursements = collect();

        // Standard stats based on roles
        // 1. PERSONAL STATS (For everyone)
        $myRequestsQuery = Reimbursement::where('user_id', $user->id)->applyTimeFilters($request);
        $stats['personal'] = [
            'pending_count' => (clone $myRequestsQuery)->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->count(),
            'approved_count' => (clone $myRequestsQuery)->where('status', 'aprobado')->count(),
            'rejected_count' => (clone $myRequestsQuery)->where('status', 'rechazado')->count(),
            'correction_count' => (clone $myRequestsQuery)->where('status', 'requiere_correccion')->count(),
            'pending_amount' => (clone $myRequestsQuery)->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->sum(DB::raw('total + COALESCE(propina, 0)')),
            'approved_amount' => (clone $myRequestsQuery)->where('status', 'aprobado')->sum(DB::raw('total + COALESCE(propina, 0)')),
        ];

        // 2. MANAGEMENT STATS (For Approvers/Admins)
        if ($user->isAdmin() || $user->isAdminView()) {
            $stats['management'] = [
                'pending_amount' => Reimbursement::applyTimeFilters($request)->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->sum(DB::raw('total + COALESCE(propina, 0)')),
                'approved_amount' => Reimbursement::applyTimeFilters($request)->where('status', 'aprobado')->sum(DB::raw('total + COALESCE(propina, 0)')),
            ];
            $stats['management']['pending_count'] = Reimbursement::applyTimeFilters($request)->whereNotIn('status', ['aprobado', 'rechazado', 'borrador'])->count();
            $stats['management']['approved_count'] = Reimbursement::applyTimeFilters($request)->where('status', 'aprobado')->count();
            $recentReimbursements = (clone $myRequestsQuery)->with('costCenter')->latest()->limit(10)->get();

        } else {
            // DYNAMIC MANAGEMENT STATS: For anyone assigned as an approver in any cost center step
            $scopedCcIds = CostCenter::whereHas('approvalSteps', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->pluck('id');

            if ($scopedCcIds->isNotEmpty()) {
                $pendingFlowQuery = Reimbursement::whereIn('cost_center_id', $scopedCcIds)
                    ->applyTimeFilters($request)
                    ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador']);

                $approvedFlowQuery = Reimbursement::whereIn('cost_center_id', $scopedCcIds)
                    ->applyTimeFilters($request)
                    ->where('status', 'aprobado');

                $stats['management'] = [
                    'pending_count' => $pendingFlowQuery->count(),
                    'pending_amount' => $pendingFlowQuery->sum(DB::raw('total + COALESCE(propina, 0)')),
                    'approved_count' => $approvedFlowQuery->count(),
                    'approved_amount' => $approvedFlowQuery->sum(DB::raw('total + COALESCE(propina, 0)')),
                    'label' => 'Asignados',
                ];
            }

            $recentReimbursements = (clone $myRequestsQuery)->with('costCenter')->latest()->limit(10)->get();
        }

        // New Detailed Analytics (Available for Admins and Managers)
        $analytics = $this->getAnalyticsData($user, $request);
        $notifications = $user->unreadNotifications()->latest()->take(5)->get();

        return view('dashboard', compact('stats', 'recentReimbursements', 'notifications', 'analytics', 'periods'));
    }

    private function getAnalyticsData($user, Request $request)
    {
        $queryBuilder = Reimbursement::query()->applyTimeFilters($request)->whereNotIn('status', ['rechazado', 'borrador']);

        // Limit scope if not admin
        if (!$user->isAdmin() && !$user->isAdminView()) {
            $queryBuilder->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter.approvalSteps', function($q2) use ($user) {
                      $q2->where('user_id', $user->id);
                  });
            });
        }

        // 1. Status Breakdown (Including all statuses for the doughnut chart)
        $statusQuery = Reimbursement::query()->applyTimeFilters($request)->where('status', '!=', 'borrador');
        if (!$user->isAdmin() && !$user->isAdminView()) {
            $statusQuery->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter.approvalSteps', function($q2) use ($user) {
                      $q2->where('user_id', $user->id);
                  });
            });
        }
        
        $rawBreakdown = $statusQuery
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $allStatuses = [
            'pendiente' => 'En Aprobación',
            'aprobado_director' => 'Aprob. Director (Old)',
            'aprobado_control' => 'Aprob. Control (Old)',
            'aprobado_ejecutivo' => 'Aprob. Ejecut. (Old)',
            'aprobado_cxp' => 'Aprob. Subdir. (Old)',
            'aprobado_direccion' => 'Aprob. Direcc. (Old)',
            'pendiente_revision_cxp' => 'CXP Revisadores',
            'pendiente_pago' => 'CXP Pagadores',
            'aprobado' => 'Pago aprobado',
            'rechazado' => 'Rechazado',
            'requiere_correccion' => 'Para Corregir',
            'en_evento' => 'En Evento',
            'borrador' => 'Borrador',
        ];

        $statusBreakdown = collect($allStatuses)->map(function ($label, $key) use ($rawBreakdown) {
            $data = $rawBreakdown->get($key);
            return (object)[
                'status' => $key,
                'label' => $label,
                'count' => $data->count ?? 0,
                'amount' => (float)($data->amount ?? 0),
            ];
        })->values();

        // 1.1 Detailed Items for Chart (Ungrouped)
        // Fetch up to 30 recent pending/in-process items to show individual slices
        $detailedItems = Reimbursement::query()->applyTimeFilters($request);
        if (!$user->isAdmin() && !$user->isAdminView()) {
            $detailedItems->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter.approvalSteps', function($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                  });
            });
        }
        $detailedItems = $detailedItems->whereNotIn('status', ['aprobado', 'rechazado'])
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get(['id', 'status', 'total', 'folio', 'uuid']);

        // 2. Weekly Totals & Growth
        $weeklyTotals = (clone $queryBuilder)
            ->select('week', DB::raw('sum(total + COALESCE(propina, 0)) as amount'), DB::raw('count(*) as count'))
            ->whereNotNull('week')
            ->groupBy('week')
            ->orderBy('week', 'desc')
            ->limit(8)
            ->get()
            ->reverse();

        $currentWeekAmount = $weeklyTotals->last()->amount ?? 0;
        $previousWeekAmount = $weeklyTotals->slice(-2, 1)->first()->amount ?? 0;
        $weekGrowth = $previousWeekAmount > 0 ? (($currentWeekAmount - $previousWeekAmount) / $previousWeekAmount) * 100 : 0;

        // 3. Average Approval Time (Hours) by Cost Center
        $avgTimeByCostCenter = (clone $queryBuilder)
            ->where('status', 'aprobado')
            ->whereNotNull('approved_by_treasury_at')
            ->select('cost_center_id', DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_by_treasury_at)) as avg_hours'))
            ->groupBy('cost_center_id')
            ->with('costCenter')
            ->orderBy('avg_hours', 'asc')
            ->limit(5)
            ->get();

        // 4. Top Spenders (Users)
        $topSpenders = (clone $queryBuilder)
            ->select('user_id', DB::raw('sum(total + COALESCE(propina, 0)) as amount'), DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->with('user')
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        // 5. Category Breakdown
        $categoryBreakdown = (clone $queryBuilder)
            ->select('category', DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get();

        // 6. Tax Recovery and XML Coverage
        $ivaRate = 0.16;
        $ivaFactor = $ivaRate / (1 + $ivaRate);
        $taxSummary = (clone $queryBuilder)
            ->select(
                DB::raw('count(*) as total_count'),
                DB::raw("sum(case when xml_path is not null and xml_path <> '' then 1 else 0 end) as with_xml_count"),
                DB::raw("sum(case when xml_path is null or xml_path = '' then 1 else 0 end) as without_xml_count"),
                DB::raw("sum(case when xml_path is not null and xml_path <> '' then coalesce(total, 0) else 0 end) as with_xml_total"),
                DB::raw("sum(case when xml_path is null or xml_path = '' then coalesce(total, 0) else 0 end) as without_xml_total"),
                DB::raw("sum(case when xml_path is not null and xml_path <> '' then coalesce(subtotal, 0) else 0 end) as subtotal"),
                DB::raw("sum(case when xml_path is not null and xml_path <> '' then coalesce(nullif(impuestos, 0), coalesce(total, 0) - coalesce(subtotal, 0), 0) else 0 end) as taxes"),
                DB::raw('sum(coalesce(total, 0)) as total'),
                DB::raw("sum(case when xml_path is null or xml_path = '' then coalesce(total, 0) * {$ivaFactor} else 0 end) as lost_iva_estimate")
            )
            ->first();

        // 7. Last 14 Days Activity (Daily)
        $dailyActivity = (clone $queryBuilder)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(total + COALESCE(propina, 0)) as amount'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return [
            'status_breakdown' => $statusBreakdown,
            'detailed_items' => $detailedItems, // Added this
            'weekly_totals' => $weeklyTotals,
            'week_growth' => $weekGrowth,
            'avg_time_by_cost_center' => $avgTimeByCostCenter,
            'top_spenders' => $topSpenders,
            'category_breakdown' => $categoryBreakdown,
            'tax_summary' => $taxSummary,
            'daily_activity' => $dailyActivity,
            'avg_ticket' => (clone $queryBuilder)->avg(DB::raw('total + COALESCE(propina, 0)')) ?: 0
        ];
    }
}
