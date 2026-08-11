<?php

namespace App\Notifications;

use App\Support\NotificationRouteHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimbursementNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $reimbursement;
    public $message;
    public $type;

    /**
     * Create a new notification instance.
     */
    public function __construct($reimbursement, $message, $type = 'info')
    {
        $this->reimbursement = $reimbursement;
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $item = $this->reimbursement ? ReimbursementNotificationContext::from($this->reimbursement) : null;
        $folio = $item['folio'] ?? 'VARIOS';
        $actionUrl = NotificationRouteHelper::reimbursement($this->reimbursement);

        $details = $item ? [
            'Folio' => $item['folio'],
            'Qué está pasando' => $item['status'],
            'Etapa' => $item['stage'],
            'Solicitante' => $item['requester'],
            'Centro de costos' => $item['cost_center'],
            'Importe' => '$' . number_format($item['amount'], 2) . ' ' . $item['currency'],
            'Acción requerida' => $item['action'],
        ] : [];

        return (new MailMessage)
            ->subject('Reembolso requiere atención: ' . $folio)
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => $this->message . ' Revisa el detalle para saber exactamente qué está pasando y qué debes hacer.',
                'actionUrl' => $actionUrl,
                'actionText' => 'Abrir reembolso',
                'details' => $details,
                'items' => $item ? [$item] : [],
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
            'reimbursement_id' => $this->reimbursement ? $this->reimbursement->id : null,
            'reimbursement_folio' => $this->reimbursement ? ReimbursementNotificationContext::folio($this->reimbursement) : 'VARIOS',
            'message' => $this->message . ' Folio: ' . ($this->reimbursement ? ReimbursementNotificationContext::folio($this->reimbursement) : 'VARIOS') . '.',
            'type' => $this->type, // success, danger, warning, info
            'url' => NotificationRouteHelper::reimbursement($this->reimbursement),
        ];
    }
}
