<?php

namespace App\Http\Controllers;

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

        // Check if there is a URL to redirect to
        if (isset($notification->data['url'])) {
            $url = $notification->data['url'];
            
            // If it's a "show" route and user is admin, they have access to everything
            if (str_contains($url, '/reimbursements/') && $user->isAdmin()) {
                return redirect($url);
            }
            
            // For other users, the ReimbursementController@show already handles sequential visibility
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
