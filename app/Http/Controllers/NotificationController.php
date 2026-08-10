<?php

namespace App\Http\Controllers;

use App\Support\NotificationRouteHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notifications.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(15);
        
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (!$url && !empty($notification->data['reimbursement_id'])) {
            $url = route('reimbursements.show', $notification->data['reimbursement_id']);
        }

        if (!$url && !empty($notification->data['reimbursement_ids'])) {
            $url = NotificationRouteHelper::reimbursementsByIds((array) $notification->data['reimbursement_ids'], 'management');
        }

        if ($url) {
            return redirect($url);
        }

        return redirect()->route('reimbursements.index');
    }

    /**
     * Mark all notifications as read for the user.
     */
    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
}
