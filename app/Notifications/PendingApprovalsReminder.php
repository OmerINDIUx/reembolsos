<?php

namespace App\Notifications;

use App\Support\NotificationRouteHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingApprovalsReminder extends Notification
{
    use Queueable;

    protected $pendingCount;
    protected $totalAmount;
    protected $breakdown;
    protected $reimbursementIds;

    /**
     * Create a new notification instance.
     */
    public function __construct($pendingCount, $totalAmount = 0, $breakdown = [], $reimbursementIds = [])
    {
        $this->pendingCount = $pendingCount;
        $this->totalAmount = $totalAmount;
        $this->breakdown = $breakdown;
        $this->reimbursementIds = collect($reimbursementIds)->filter()->unique()->values()->all();
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
    public function toMail(object $notifiable): array|MailMessage
    {
        $actionUrl = NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management');

        return (new MailMessage)
            ->subject('Recordatorio: Tienes Reembolsos Pendientes de Aprobar')
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => 'Este es un recordatorio semanal de que tienes <span class="highlight">' . $this->pendingCount . '</span> solicitud(es) de reembolso pendientes de tu revisión y aprobación.',
                'actionUrl' => $actionUrl,
                'actionText' => $this->pendingCount === 1 ? 'Ver Solicitud' : 'Ver Solicitudes',
                'details' => [
                    'Total acumulado' => '$' . number_format($this->totalAmount, 2),
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
            'message' => "Tienes {$this->pendingCount} reembolsos pendientes de aprobar.",
            'pending_count' => $this->pendingCount,
            'reimbursement_ids' => $this->reimbursementIds,
            'url' => NotificationRouteHelper::reimbursementsByIds($this->reimbursementIds, 'management'),
            'type' => 'warning',
        ];
    }
}
