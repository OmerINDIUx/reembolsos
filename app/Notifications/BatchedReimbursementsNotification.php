<?php

namespace App\Notifications;

use App\Support\NotificationRouteHelper;
use App\Support\ReimbursementNotificationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchedReimbursementsNotification extends Notification
{
    use Queueable;

    protected $reimbursements;
    protected $count;
    protected $totalAmount;
    protected $breakdown;
    protected $reimbursementIds;
    protected $items;

    public function __construct($reimbursements)
    {
        $this->reimbursements = $reimbursements->values();
        $this->reimbursementIds = $this->reimbursements->pluck('id')->filter()->unique()->values()->all();
        $this->count = $this->reimbursements->count();
        $this->totalAmount = $this->reimbursements->sum(fn($r) => (float) $r->total + (float) ($r->propina ?? 0));
        $this->items = $this->reimbursements
            ->map(fn($reimbursement) => ReimbursementNotificationContext::from($reimbursement))
            ->values()
            ->all();

        $this->breakdown = [];
        foreach ($this->items as $item) {
            $ccName = $item['cost_center'];
            if (!isset($this->breakdown[$ccName])) {
                $this->breakdown[$ccName] = ['count' => 0, 'total' => 0];
            }
            $this->breakdown[$ccName]['count']++;
            $this->breakdown[$ccName]['total'] += $item['amount'];
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management');
        $folios = collect($this->items)->pluck('folio')->implode(', ');
        $bodyText = $this->count === 1
            ? 'El reembolso <span class="highlight">' . e($folios) . '</span> requiere tu atención. Revisa abajo exactamente qué está pasando y cuál es la acción siguiente.'
            : 'Has recibido <span class="highlight">' . $this->count . '</span> reembolsos que requieren seguimiento. Los folios y la acción requerida de cada uno aparecen abajo.';

        return (new MailMessage)
            ->subject(($this->count === 1 ? 'Reembolso requiere atención: ' : 'Reembolsos requieren atención: ') . $folios)
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => $bodyText,
                'actionUrl' => $actionUrl,
                'actionText' => $this->count === 1 ? 'Abrir reembolso' : 'Abrir reembolsos',
                'details' => [
                    'Cantidad' => $this->count . ' reembolso(s)',
                    'Monto total' => '$' . number_format($this->totalAmount, 2) . ' MXN',
                    'Folios' => $folios,
                    'Qué debes hacer' => 'Consulta la acción requerida en el detalle de cada folio.',
                ],
                'items' => $this->items,
                'breakdown' => $this->breakdown,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $folios = collect($this->items)->pluck('folio')->values()->all();
        $message = $this->count === 1
            ? 'El reembolso ' . $folios[0] . ' requiere atención: ' . $this->items[0]['status'] . '.'
            : 'Tienes ' . $this->count . ' reembolsos que requieren atención: ' . implode(', ', $folios) . '.';

        return [
            'count' => $this->count,
            'total' => $this->totalAmount,
            'reimbursement_id' => $this->count === 1 ? $this->reimbursementIds[0] : null,
            'reimbursement_folio' => $this->count === 1 ? $folios[0] : 'VARIOS (' . $this->count . ')',
            'reimbursement_folios' => $folios,
            'items' => $this->items,
            'message' => $message,
            'type' => 'info',
            'url' => NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management'),
        ];
    }
}
