<?php

namespace App\Notifications;

use App\Models\DeviceLogin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighRiskLoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly ?DeviceLogin $deviceLogin = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de seguridad para iniciar sesión')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Detectamos un inicio de sesión con señales de riesgo alto.')
            ->line('Para proteger tu cuenta, ingresa este código en el sistema:')
            ->line($this->code)
            ->line('El código vence en 10 minutos.')
            ->line('Dispositivo: ' . ($this->deviceLogin?->device_label ?: 'Dispositivo desconocido'))
            ->line('Ubicación aproximada: ' . ($this->deviceLogin?->approx_location ?: 'No disponible'))
            ->line('Si no fuiste tú, avisa a un administrador inmediatamente.');
    }
}
