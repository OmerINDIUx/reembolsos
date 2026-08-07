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

    private function hydrateLegacyNotificationDetails($notifications): void
    {
        $ids = $notifications->getCollection()
            ->flatMap(fn($notification) => array_merge(
                (array) ($notification->data['reimbursement_ids'] ?? []),
                !empty($notification->data['reimbursement_id']) ? [$notification->data['reimbursement_id']] : []
            ))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $reimbursements = Reimbursement::with(['costCenter', 'currentStep', 'user', 'payee', 'createdBy'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $notifications->getCollection()->transform(function ($notification) use ($reimbursements) {
            $data = $notification->data;
            $notificationIds = collect(array_merge(
                (array) ($data['reimbursement_ids'] ?? []),
                !empty($data['reimbursement_id']) ? [$data['reimbursement_id']] : []
            ))->filter()->unique()->values();
            $items = $notificationIds
                ->map(fn($id) => $reimbursements->get($id))
                ->filter()
                ->map(fn($reimbursement) => ReimbursementNotificationContext::from($reimbursement))
                ->values()
                ->all();

            if ($items !== []) {
                $folios = collect($items)->pluck('folio')->values()->all();
                $data['reimbursement_folio'] = count($folios) === 1 ? $folios[0] : 'VARIOS (' . count($folios) . ')';
                $data['reimbursement_folios'] = $folios;
                $data['items'] = $items;

                if (str_contains((string) ($data['message'] ?? ''), 'nuevas notificaciones de reembolso')) {
                    $data['message'] = count($folios) === 1
                        ? 'El reembolso ' . $folios[0] . ' requiere atención: ' . $items[0]['status'] . '.'
                        : 'Tienes ' . count($folios) . ' reembolsos que requieren atención: ' . implode(', ', $folios) . '.';
                }
            }

            $notification->data = $data;
            return $notification;
        });
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
