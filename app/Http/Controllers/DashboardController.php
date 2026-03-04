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
    public function index()
    {
        $user = Auth::user();
        $stats = [];
        $recentReimbursements = collect();

        // Standard stats based on roles
        if ($user->isAdmin() || $user->isAdminView()) {
            $stats['pending_count'] = Reimbursement::whereNotIn('status', ['aprobado', 'rechazado'])->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['rejected_count'] = Reimbursement::where('status', 'rechazado')->count();
            $stats['total_amount_pending'] = Reimbursement::whereNotIn('status', ['aprobado', 'rechazado'])->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');
            $stats['total_amount_rejected'] = Reimbursement::where('status', 'rechazado')->sum('total');
            
            $recentReimbursements = Reimbursement::with('user', 'costCenter')->latest()->paginate(10);

        } elseif ($user->isCxp()) {
            $stats['pending_count'] = Reimbursement::where('status', 'aprobado_ejecutivo')->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['total_amount_pending'] = Reimbursement::where('status', 'aprobado_ejecutivo')->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');
            $recentReimbursements = Reimbursement::whereIn('status', ['aprobado_ejecutivo', 'aprobado_cxp', 'aprobado_direccion', 'aprobado'])
                                    ->with('user', 'costCenter')->latest()->paginate(10);

        } elseif ($user->isDireccion()) {
            $stats['pending_count'] = Reimbursement::where('status', 'aprobado_cxp')->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['total_amount_pending'] = Reimbursement::where('status', 'aprobado_cxp')->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');
            $recentReimbursements = Reimbursement::whereIn('status', ['aprobado_cxp', 'aprobado_direccion', 'aprobado'])
                                    ->with('user', 'costCenter')->latest()->paginate(10);

        } elseif ($user->isTreasury()) {
            $stats['pending_count'] = Reimbursement::where('status', 'aprobado_direccion')->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['total_amount_pending'] = Reimbursement::where('status', 'aprobado_direccion')->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');
            $recentReimbursements = Reimbursement::whereIn('status', ['aprobado_direccion', 'aprobado'])
                                    ->with('user', 'costCenter')->latest()->paginate(10);

        } elseif ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
            $pendingApprovalsQuery = Reimbursement::whereHas('costCenter', function($q) use ($user) {
                if ($user->isDirector()) $q->where('director_id', $user->id);
                if ($user->isControlObra()) $q->where('control_obra_id', $user->id);
                if ($user->isExecutiveDirector()) $q->where('director_ejecutivo_id', $user->id);
            });

            if ($user->isDirector()) $pendingApprovalsQuery->where('status', 'pendiente');
            if ($user->isControlObra()) $pendingApprovalsQuery->where('status', 'aprobado_director');
            if ($user->isExecutiveDirector()) $pendingApprovalsQuery->where('status', 'aprobado_control');

            $levelLabel = $user->isDirector() ? 'N1' : ($user->isControlObra() ? 'N2' : 'N3');
            $stats['pending_approvals_count'] = $pendingApprovalsQuery->count();
            $stats['pending_approvals_amount'] = $pendingApprovalsQuery->sum('total');
            $stats['approval_level_label'] = $levelLabel;

            $myRequestsQuery = Reimbursement::where('user_id', $user->id);
            $stats['my_pending_count'] = (clone $myRequestsQuery)->whereNotIn('status', ['aprobado', 'rechazado'])->count();
            $stats['my_approved_count'] = (clone $myRequestsQuery)->where('status', 'aprobado')->count();
            $stats['my_total_reimbursed'] = (clone $myRequestsQuery)->where('status', 'aprobado')->sum('total');

            $recentReimbursements = Reimbursement::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter', function($q2) use ($user) {
                      if ($user->isDirector()) $q2->where('director_id', $user->id);
                      if ($user->isControlObra()) $q2->where('control_obra_id', $user->id);
                      if ($user->isExecutiveDirector()) $q2->where('director_ejecutivo_id', $user->id);
                  });
            })->with('user', 'costCenter')->latest()->paginate(10);

        } else {
            $myRequestsQuery = Reimbursement::where('user_id', $user->id);
            $stats['pending_count'] = (clone $myRequestsQuery)->whereNotIn('status', ['aprobado', 'rechazado'])->count();
            $stats['approved_count'] = (clone $myRequestsQuery)->where('status', 'aprobado')->count();
            $stats['rejected_count'] = (clone $myRequestsQuery)->where('status', 'rechazado')->count();
            $stats['correction_count'] = (clone $myRequestsQuery)->where('status', 'requiere_correccion')->count();
            $stats['total_pending_amount'] = (clone $myRequestsQuery)->whereNotIn('status', ['aprobado', 'rechazado'])->sum('total');
            $stats['total_approved_amount'] = (clone $myRequestsQuery)->where('status', 'aprobado')->sum('total');
            $recentReimbursements = $myRequestsQuery->with('costCenter')->latest()->paginate(10);
        }

        // New Detailed Analytics (Available for Admins and Managers)
        $analytics = $this->getAnalyticsData($user);
        $notifications = $user->unreadNotifications()->latest()->take(5)->get();

        return view('dashboard', compact('stats', 'recentReimbursements', 'notifications', 'analytics'));
    }

    private function getAnalyticsData($user)
    {
        $queryBuilder = Reimbursement::query()->where('status', '!=', 'rechazado');

        // Limit scope if not admin
        if (!$user->isAdmin() && !$user->isAdminView() && !$user->isCxp() && !$user->isDireccion() && !$user->isTreasury()) {
            $queryBuilder->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter', function($q2) use ($user) {
                      if ($user->isDirector()) $q2->where('director_id', $user->id);
                      if ($user->isControlObra()) $q2->where('control_obra_id', $user->id);
                      if ($user->isExecutiveDirector()) $q2->where('director_ejecutivo_id', $user->id);
                  });
            });
        }

        // 1. Status Breakdown (Including all statuses for the doughnut chart)
        $statusQuery = Reimbursement::query();
        if (!$user->isAdmin() && !$user->isAdminView() && !$user->isCxp() && !$user->isDireccion() && !$user->isTreasury()) {
            $statusQuery->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter', function($q2) use ($user) {
                      if ($user->isDirector()) $q2->where('director_id', $user->id);
                      if ($user->isControlObra()) $q2->where('control_obra_id', $user->id);
                      if ($user->isExecutiveDirector()) $q2->where('director_ejecutivo_id', $user->id);
                  });
            });
        }
        $statusBreakdown = $statusQuery
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total) as amount'))
            ->groupBy('status')
            ->get();

        // 2. Weekly Totals & Growth
        $weeklyTotals = (clone $queryBuilder)
            ->select('week', DB::raw('sum(total) as amount'), DB::raw('count(*) as count'))
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
            ->select('user_id', DB::raw('sum(total) as amount'), DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->with('user')
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        // 5. Category Breakdown
        $categoryBreakdown = (clone $queryBuilder)
            ->select('category', DB::raw('sum(total) as amount'))
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get();

        // 6. Tax Recovery (Impuestos vs Subtotal)
        $taxSummary = (clone $queryBuilder)
            ->select(DB::raw('sum(subtotal) as subtotal'), DB::raw('sum(impuestos) as taxes'), DB::raw('sum(total) as total'))
            ->first();

        // 7. Last 14 Days Activity (Daily)
        $dailyActivity = (clone $queryBuilder)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(total) as amount'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return [
            'status_breakdown' => $statusBreakdown,
            'weekly_totals' => $weeklyTotals,
            'week_growth' => $weekGrowth,
            'avg_time_by_cost_center' => $avgTimeByCostCenter,
            'top_spenders' => $topSpenders,
            'category_breakdown' => $categoryBreakdown,
            'tax_summary' => $taxSummary,
            'daily_activity' => $dailyActivity,
            'avg_ticket' => (clone $queryBuilder)->avg('total') ?: 0
        ];
    }
}

