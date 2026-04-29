<?php

namespace App\Notifications;

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

    /**
     * Create a new notification instance.
     */
    public function __construct($reimbursements)
    {
        $this->reimbursements = $reimbursements;
        $this->count = $reimbursements->count();
        $this->totalAmount = $reimbursements->sum('total');
        
        $this->breakdown = [];
        foreach ($reimbursements as $r) {
            $ccName = $r->costCenter->name ?? 'Sin Centro de Costos';
            if (!isset($this->breakdown[$ccName])) {
                $this->breakdown[$ccName] = ['count' => 0, 'total' => 0];
            }
            $this->breakdown[$ccName]['count']++;
            $this->breakdown[$ccName]['total'] += (float)$r->total;
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
        return (new MailMessage)
            ->subject('Nuevas Notificaciones de Reembolso (Resumen)')
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => 'Has recibido <span class="highlight">' . $this->count . '</span> nuevas notificaciones de reembolso. Aquí tienes el resumen de las solicitudes que requieren tu atención.',
                'actionUrl' => route('reimbursements.index', ['tab' => 'management']),
                'actionText' => 'Ver Todo en el Sistema',
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
            'message' => 'Has recibido ' . $this->count . ' nuevas notificaciones de reembolso.',
            'type' => 'info'
        ];
    }
}
