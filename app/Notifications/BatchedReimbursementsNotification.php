<?php

namespace App\Notifications;

use App\Support\NotificationRouteHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    /**
     * Create a new notification instance.
     */
    public function __construct($reimbursements)
    {
        $this->reimbursements = $reimbursements;
        $this->reimbursementIds = $reimbursements->pluck('id')->filter()->unique()->values()->all();
        $this->count = $reimbursements->count();
        $this->totalAmount = $reimbursements->sum(fn($r) => (float) $r->total + (float) ($r->propina ?? 0));
        
        $this->breakdown = [];
        foreach ($reimbursements as $r) {
            $ccName = $r->costCenter->name ?? 'Sin Centro de Costos';
            if (!isset($this->breakdown[$ccName])) {
                $this->breakdown[$ccName] = ['count' => 0, 'total' => 0];
            }
            $this->breakdown[$ccName]['count']++;
            $this->breakdown[$ccName]['total'] += (float)$r->total + (float)($r->propina ?? 0);
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management');

        return (new MailMessage)
            ->subject('Nuevas Notificaciones de Reembolso (Resumen)')
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => 'Has recibido <span class="highlight">' . $this->count . '</span> nuevas notificaciones de reembolso. Aquí tienes el resumen de las solicitudes que requieren tu atención.',
                'actionUrl' => $actionUrl,
                'actionText' => $this->count === 1 ? 'Ver Solicitud' : 'Ver Solicitudes',
                'details' => [
                    'Cantidad Total' => $this->count . ' reembolsos',
                    'Monto Total' => '$' . number_format($this->totalAmount, 2),
                    'Estado' => 'Pendientes / Asignados',
                ],
                'breakdown' => $this->breakdown
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'count' => $this->count,
            'total' => $this->totalAmount,
            'reimbursement_ids' => $this->reimbursementIds,
            'message' => 'Has recibido ' . $this->count . ' nuevas notificaciones de reembolso.',
            'type' => 'info',
            'url' => NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management'),
        ];
    }
}
