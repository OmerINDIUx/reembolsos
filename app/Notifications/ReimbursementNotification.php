<?php

namespace App\Notifications;

use App\Support\NotificationRouteHelper;
use App\Support\ReimbursementNotificationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimbursementNotification extends Notification
{
    use Queueable;

    public $reimbursement;
    public $message;
    public $type;

    public function __construct(
        $reimbursement,
        $message,
        $type = 'info',
        private readonly bool $sendEmail = true,
        private readonly string $category = 'workflow',
    ) {
        $this->reimbursement = $reimbursement;
        $this->message = $message;
        $this->type = $type;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($this->sendEmail && $notifiable->wantsEmailNotification($this->category)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item = $this->reimbursement ? ReimbursementNotificationContext::from($this->reimbursement) : null;
        $folio = $item['folio'] ?? 'VARIOS';
        $actionUrl = NotificationRouteHelper::reimbursement($this->reimbursement);

        $details = $item ? [
            'Folio' => $item['folio'],
            'Qué está pasando' => $item['status'],
            'Etapa actual' => $item['stage'],
            'Solicitante' => $item['requester'],
            'Beneficiario' => $item['payee'],
            'Centro de costos' => $item['cost_center'],
            'Importe' => '$' . number_format($item['amount'], 2) . ' ' . $item['currency'],
            'Qué sigue' => $item['action'],
        ] : [];

        return (new MailMessage)
            ->subject($this->mailSubject($folio, $item['status_code'] ?? null))
            ->view('emails.notification', [
                'greeting' => 'Hola ' . $notifiable->name . ',',
                'bodyText' => $this->message,
                'actionUrl' => $actionUrl,
                'actionText' => 'Abrir reembolso',
                'details' => $details,
                'items' => $item ? [$item] : [],
            ]);
    }

    private function mailSubject(string $folio, ?string $status): string
    {
        return match ($status) {
            'requiere_correccion' => 'Corrección requerida en el reembolso ' . $folio,
            'rechazado' => 'Reembolso rechazado: ' . $folio,
            'pagado' => 'Pago confirmado del reembolso ' . $folio,
            default => 'Actualización del reembolso ' . $folio,
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reimbursement_id' => $this->reimbursement?->id,
            'reimbursement_folio' => $this->reimbursement ? ReimbursementNotificationContext::folio($this->reimbursement) : 'VARIOS',
            'message' => $this->message . ' Folio: ' . ($this->reimbursement ? ReimbursementNotificationContext::folio($this->reimbursement) : 'VARIOS') . '.',
            'type' => $this->type,
            'url' => NotificationRouteHelper::reimbursement($this->reimbursement),
        ];
    }
}
