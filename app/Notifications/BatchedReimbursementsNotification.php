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

    public function __construct($reimbursements, protected string $category = 'workflow')
    {
        $this->reimbursements = $reimbursements->values();
        $this->reimbursementIds = $this->reimbursements->pluck('id')->filter()->unique()->values()->all();
        $this->count = $this->reimbursements->count();
        $this->totalAmount = $this->reimbursements->sum(fn ($reimbursement) => (float) $reimbursement->total + (float) ($reimbursement->propina ?? 0));
        $this->items = $this->reimbursements
            ->map(fn ($reimbursement) => ReimbursementNotificationContext::from($reimbursement))
            ->values()
            ->all();

        $this->breakdown = [];
        foreach ($this->items as $item) {
            $costCenter = $item['cost_center'];
            $this->breakdown[$costCenter] ??= ['count' => 0, 'total' => 0];
            $this->breakdown[$costCenter]['count']++;
            $this->breakdown[$costCenter]['total'] += $item['amount'];
        }
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->wantsEmailNotification($this->category)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isPayment = $this->category === 'payment';
        $allPaid = collect($this->items)->every(fn (array $item) => $item['status_code'] === 'pagado');
        $folios = collect($this->items)->pluck('folio')->implode(', ');
        $actionUrl = NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, $isPayment ? 'payment' : 'management');

        if ($isPayment && ! $allPaid) {
            $subject = ($this->count === 1 ? 'Reembolso disponible' : 'Reembolsos disponibles') . ' en Módulo de Pago: ' . $folios;
            $bodyText = 'La autorización de CXP Pagadores terminó. ' . ($this->count === 1 ? 'El reembolso indicado ya aparece' : 'Los reembolsos indicados ya aparecen') . ' en el <span class="highlight">Módulo de Pago</span> para generar o confirmar el pago.';
            $actionText = 'Abrir Módulo de Pago';
        } elseif ($allPaid) {
            $subject = ($this->count === 1 ? 'Pago confirmado del reembolso ' : 'Pagos confirmados: ') . $folios;
            $bodyText = $this->count === 1
                ? 'El pago de este reembolso ya fue registrado en el sistema. Consulta abajo el folio, beneficiario, importe y estado final.'
                : 'Se registró el pago de <span class="highlight">' . $this->count . '</span> reembolsos. Consulta abajo el detalle de cada operación.';
            $actionText = 'Consultar pago';
        } else {
            $subject = ($this->count === 1 ? 'Actualización del reembolso ' : 'Actualizaciones de reembolsos: ') . $folios;
            $bodyText = $this->count === 1
                ? 'El reembolso cambió de estado. El detalle inferior explica qué ocurrió, en qué etapa se encuentra y cuál es la siguiente acción.'
                : 'Hay <span class="highlight">' . $this->count . '</span> reembolsos con cambios relevantes. Cada tarjeta indica el estado actual y la acción siguiente.';
            $actionText = $this->count === 1 ? 'Abrir reembolso' : 'Abrir reembolsos';
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => $bodyText,
                'actionUrl' => $actionUrl,
                'actionText' => $actionText,
                'details' => [
                    'Cantidad' => $this->count . ' reembolso(s)',
                    'Monto total' => '$' . number_format($this->totalAmount, 2) . ' MXN',
                    'Folios' => $folios,
                    'Resultado' => $isPayment && ! $allPaid
                        ? 'Autorizados por CXP Pagadores y disponibles en Módulo de Pago.'
                        : 'Consulta el estado y la acción siguiente de cada folio.',
                ],
                'items' => $this->items,
                'breakdown' => $this->breakdown,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $folios = collect($this->items)->pluck('folio')->values()->all();
        $message = $this->count === 1
            ? 'El reembolso ' . $folios[0] . ' cambió a: ' . $this->items[0]['status'] . '. Siguiente acción: ' . $this->items[0]['action']
            : 'Hay ' . $this->count . ' reembolsos con cambios: ' . implode(', ', $folios) . '.';

        return [
            'count' => $this->count,
            'total' => $this->totalAmount,
            'category' => $this->category,
            'reimbursement_id' => $this->count === 1 ? $this->reimbursementIds[0] : null,
            'reimbursement_folio' => $this->count === 1 ? $folios[0] : 'VARIOS (' . $this->count . ')',
            'reimbursement_folios' => $folios,
            'items' => $this->items,
            'message' => $message,
            'type' => 'info',
            'url' => NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, $this->category === 'payment' ? 'payment' : 'management'),
        ];
    }
}
