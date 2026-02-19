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
        if ($user->isAdmin() || $user->isAccountant()) {
            // General Stats for Company
            $stats['pending_count'] = Reimbursement::where('status', 'pendiente')->count();
            $stats['approved_count'] = Reimbursement::where('status', 'aprobado')->count();
            $stats['rejected_count'] = Reimbursement::where('status', 'rechazado')->count();
            $stats['total_amount_pending'] = Reimbursement::where('status', 'pendiente')->sum('total');
            $stats['total_amount_approved'] = Reimbursement::where('status', 'aprobado')->sum('total');
            
            $recentReimbursements = Reimbursement::with('user', 'costCenter')->latest()->take(5)->get();

        } elseif ($user->isDirector()) {
            // Director sees approvals for their cost centers AND their own requests
            
            // Pending Approvals (where they are the director of the cost center)
            $pendingApprovalsQuery = Reimbursement::whereHas('costCenter', function($q) use ($user) {
                $q->where('director_id', $user->id);
            })->where('status', 'pendiente');

            $stats['pending_approvals_count'] = $pendingApprovalsQuery->count();
            $stats['pending_approvals_amount'] = $pendingApprovalsQuery->sum('total');

            // Their Own Requests
            $myRequestsQuery = Reimbursement::where('user_id', $user->id);
            $stats['my_pending_count'] = (clone $myRequestsQuery)->where('status', 'pendiente')->count();
            $stats['my_approved_count'] = (clone $myRequestsQuery)->where('status', 'aprobado')->count();
            $stats['my_total_reimbursed'] = (clone $myRequestsQuery)->where('status', 'aprobado')->sum('total');

            // Recent Activity (Approvals needed OR My requests)
            $recentReimbursements = Reimbursement::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('costCenter', function($q2) use ($user) {
                      $q2->where('director_id', $user->id);
                  });
            })->with('user', 'costCenter')->latest()->take(5)->get();

        } else {
            // Standard User
            $myRequestsQuery = Reimbursement::where('user_id', $user->id);
            
            $stats['pending_count'] = (clone $myRequestsQuery)->where('status', 'pendiente')->count();
            $stats['approved_count'] = (clone $myRequestsQuery)->where('status', 'aprobado')->count();
            $stats['rejected_count'] = (clone $myRequestsQuery)->where('status', 'rechazado')->count();
            $stats['total_pending_amount'] = (clone $myRequestsQuery)->where('status', 'pendiente')->sum('total');
            $stats['total_approved_amount'] = (clone $myRequestsQuery)->where('status', 'aprobado')->sum('total');

            $recentReimbursements = $myRequestsQuery->with('costCenter')->latest()->take(5)->get();
        }

        return view('dashboard', compact('stats', 'recentReimbursements'));
    }
}
