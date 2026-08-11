<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use App\Support\NotificationRouteHelper;
use App\Support\ReimbursementNotificationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingApprovalsReminder extends Notification
{
    use Queueable;

    protected $pendingCount;
    protected $totalAmount;
    protected $breakdown;
    protected $reimbursementIds;
    protected $items;

    public function __construct($pendingCount, $totalAmount = 0, $breakdown = [], $reimbursementIds = [])
    {
        $this->pendingCount = $pendingCount;
        $this->totalAmount = $totalAmount;
        $this->breakdown = $breakdown;
        $this->reimbursementIds = collect($reimbursementIds)->filter()->unique()->values()->all();
        $this->items = null;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->wantsEmailNotification('workflow')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function notificationItems(): array
    {
        if ($this->items === null) {
            $this->items = Reimbursement::with(['costCenter', 'currentStep', 'user', 'payee', 'createdBy'])
                ->whereIn('id', $this->reimbursementIds)
                ->get()
                ->map(fn($reimbursement) => ReimbursementNotificationContext::from($reimbursement))
                ->values()
                ->all();
        }

        return $this->items;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $items = $this->notificationItems();
        $folios = collect($items)->pluck('folio')->implode(', ');
        $actionUrl = NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management');

        return (new MailMessage)
            ->subject('Recordatorio de aprobación: ' . $folios)
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => 'Tienes <span class="highlight">' . $this->pendingCount . '</span> reembolsos pendientes de tu revisión. El detalle inferior indica el folio, la etapa actual, el importe y la acción que debes realizar.',
                'actionUrl' => $actionUrl,
                'actionText' => $this->pendingCount === 1 ? 'Abrir reembolso' : 'Abrir reembolsos',
                'details' => [
                    'Cantidad' => $this->pendingCount . ' reembolso(s)',
                    'Total acumulado' => '$' . number_format($this->totalAmount, 2) . ' MXN',
                    'Folios' => $folios ?: 'No disponibles',
                    'Acción' => 'Revisar cada solicitud y aprobarla o devolverla con un motivo claro.',
                ],
                'items' => $items,
                'breakdown' => $this->breakdown,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $items = $this->notificationItems();
        $folios = collect($items)->pluck('folio')->values()->all();

        return [
            'message' => 'Recordatorio: tienes ' . $this->pendingCount . ' reembolsos pendientes de aprobación: ' . implode(', ', $folios) . '.',
            'pending_count' => $this->pendingCount,
            'reimbursement_ids' => $this->reimbursementIds,
            'reimbursement_folios' => $folios,
            'items' => $items,
            'url' => NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management'),
            'type' => 'warning',
        ];
    }
}
