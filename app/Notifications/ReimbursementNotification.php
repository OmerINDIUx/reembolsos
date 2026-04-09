<?php

namespace App\Notifications;

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
        $folio = $this->reimbursement ? ($this->reimbursement->true_folio ?? 'S/F') : 'VARIOS';
        
        $details = [];
        if ($this->reimbursement) {
            $details = [
                'Folio' => $folio,
                'Centro de Costos' => $this->reimbursement->costCenter->name ?? 'N/A',
                'Monto' => '$' . number_format($this->reimbursement->total, 2) . ' ' . $this->reimbursement->moneda,
                'Emisor' => $this->reimbursement->nombre_emisor ?? 'N/A',
                'Estatus' => ucfirst($this->reimbursement->status),
            ];
        }

        return (new MailMessage)
            ->subject('Notificación de Reembolso: ' . $folio)
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => $this->message,
                'actionUrl' => $this->reimbursement ? route('reimbursements.show', $this->reimbursement->id) : route('reimbursements.index'),
                'actionText' => 'Ver Solicitud',
                'details' => $details
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
            'reimbursement_folio' => $this->reimbursement ? ($this->reimbursement->true_folio ?? 'S/F') : 'VARIOS',
            'message' => $this->message,
            'type' => $this->type, // success, danger, warning, info
            'url' => $this->reimbursement ? route('reimbursements.show', $this->reimbursement->id) : route('reimbursements.index')
        ];
    }
}
