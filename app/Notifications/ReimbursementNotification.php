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
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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
            'reimbursement_folio' => $this->reimbursement ? ($this->reimbursement->folio ?? substr($this->reimbursement->uuid, 0, 8) ?? 'S/F') : 'VARIOS',
            'message' => $this->message,
            'type' => $this->type, // success, danger, warning, info
            'url' => $this->reimbursement ? route('reimbursements.show', $this->reimbursement->id) : route('reimbursements.index')
        ];
    }
}
