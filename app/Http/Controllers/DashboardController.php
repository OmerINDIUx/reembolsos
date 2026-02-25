<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = [];
        $recentReimbursements = collect();

        // Admin, Accountant, or Director logic
        // Admin gets full visibility
        if ($user->isAdmin() || $user->isAdminView()) {
            $stats['pending_count'] = Reimbursement::whereIn('status', ['pendiente', 'aprobado_director', 'requiere_correccion'])->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['rejected_count'] = Reimbursement::where('status', 'rechazado')->count();
            $stats['total_amount_pending'] = Reimbursement::whereIn('status', ['pendiente', 'aprobado_director', 'requiere_correccion'])->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');
            
            $recentReimbursements = Reimbursement::with('user', 'costCenter')->latest()->take(5)->get();

        } elseif ($user->isCxp()) {
            // Cuentas por Pagar (CXP)
            // Pending for them is 'aprobado_director'
            $stats['pending_count'] = Reimbursement::where('status', 'aprobado_director')->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['total_amount_pending'] = Reimbursement::where('status', 'aprobado_director')->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');

            // Only show items approved by director or finally approved
            $recentReimbursements = Reimbursement::whereIn('status', ['aprobado_director', 'aprobado'])
                                    ->with('user', 'costCenter')
                                    ->latest()->take(5)->get();

        } elseif ($user->isDirector() || $user->isControlObra() || $user->isExecutiveDirector()) {
            // Managers (N1, N2, N3) see approvals for their cost centers AND their own requests
            
            // Pending Approvals
            $pendingApprovalsQuery = Reimbursement::whereHas('costCenter', function($q) use ($user) {
                if ($user->isDirector()) $q->where('director_id', $user->id);
                if ($user->isControlObra()) $q->where('control_obra_id', $user->id);
                if ($user->isExecutiveDirector()) $q->where('director_ejecutivo_id', $user->id);
            });

            // Status depends on level
            if ($user->isDirector()) $pendingApprovalsQuery->where('status', 'pendiente');
            if ($user->isControlObra()) $pendingApprovalsQuery->where('status', 'aprobado_director');
            if ($user->isExecutiveDirector()) $pendingApprovalsQuery->where('status', 'aprobado_control');

            $levelLabel = 'N/A';
            if ($user->isDirector()) $levelLabel = 'N1';
            if ($user->isControlObra()) $levelLabel = 'N2';
            if ($user->isExecutiveDirector()) $levelLabel = 'N3';

            $stats['pending_approvals_count'] = $pendingApprovalsQuery->count();
            $stats['pending_approvals_amount'] = $pendingApprovalsQuery->sum('total');
            $stats['approval_level_label'] = $levelLabel;

            // Their Own Requests
            $myRequestsQuery = Reimbursement::where('user_id', $user->id);
            $stats['my_pending_count'] = (clone $myRequestsQuery)->whereNotIn('status', ['aprobado', 'rechazado'])->count();
            $stats['my_approved_count'] = (clone $myRequestsQuery)->where('status', 'aprobado')->count();
            $stats['my_total_reimbursed'] = (clone $myRequestsQuery)->where('status', 'aprobado')->sum('total');

            // Recent Activity (Approvals needed OR My requests)
            $recentReimbursements = Reimbursement::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter', function($q2) use ($user) {
                      if ($user->isDirector()) $q2->where('director_id', $user->id);
                      if ($user->isControlObra()) $q2->where('control_obra_id', $user->id);
                      if ($user->isExecutiveDirector()) $q2->where('director_ejecutivo_id', $user->id);
                  });
            })->with('user', 'costCenter')->latest()->take(5)->get();

        } else {
            // Standard User
            $myRequestsQuery = Reimbursement::where('user_id', $user->id);
            
            $stats['pending_count'] = (clone $myRequestsQuery)->where('status', 'pendiente')->count();
            $stats['approved_count'] = (clone $myRequestsQuery)->where('status', 'aprobado')->count();
            $stats['rejected_count'] = (clone $myRequestsQuery)->where('status', 'rechazado')->count();
            $stats['correction_count'] = (clone $myRequestsQuery)->where('status', 'requiere_correccion')->count();
            $stats['total_pending_amount'] = (clone $myRequestsQuery)->whereIn('status', ['pendiente', 'requiere_correccion'])->sum('total');
            $stats['total_approved_amount'] = (clone $myRequestsQuery)->where('status', 'aprobado')->sum('total');

            $recentReimbursements = $myRequestsQuery->with('costCenter')->latest()->take(5)->get();
        }

        $notifications = $user->unreadNotifications()->latest()->take(5)->get();
        return view('dashboard', compact('stats', 'recentReimbursements', 'notifications'));
    }
}
