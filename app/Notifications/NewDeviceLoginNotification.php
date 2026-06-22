<?php

namespace App\Notifications;

use App\Models\DeviceLogin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewDeviceLoginNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DeviceLogin $deviceLogin)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Se inició sesión desde un dispositivo nuevo: '
                . ($this->deviceLogin->device_label ?: 'Dispositivo desconocido')
                . ' · '
                . ($this->deviceLogin->approx_location ?: 'Ubicación no disponible'),
            'type' => $this->deviceLogin->risk_score >= 50 ? 'warning' : 'info',
            'url' => route('reimbursements.index'),
            'device_login_id' => $this->deviceLogin->id,
            'device_code' => $this->deviceLogin->device_code,
            'logged_in_at' => $this->deviceLogin->logged_in_at?->toDateTimeString(),
        ];
    }
}
