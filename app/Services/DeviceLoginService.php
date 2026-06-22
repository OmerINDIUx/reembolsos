<?php

namespace App\Services;

use App\Models\DeviceLogin;
use App\Models\User;
use App\Notifications\NewDeviceLoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeviceLoginService
{
    public const COOKIE_NAME = 'reembolsos_device';

    public function record(User $user, Request $request): ?DeviceLogin
    {
        if (! Schema::hasTable('device_logins')) {
            return null;
        }

        $deviceId = $request->cookie(self::COOKIE_NAME) ?: (string) Str::uuid();
        $deviceHash = hash('sha256', $deviceId);

        if (! $request->cookie(self::COOKIE_NAME)) {
            Cookie::queue(cookie(
                self::COOKIE_NAME,
                $deviceId,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        $userAgent = Str::limit((string) $request->userAgent(), 1000, '');
        $previousDeviceLogins = DeviceLogin::where('user_id', $user->id)->exists();
        $isNewDevice = ! DeviceLogin::where('user_id', $user->id)
            ->where('device_hash', $deviceHash)
            ->exists();
        $simultaneousDevicesCount = DeviceLogin::where('user_id', $user->id)
            ->where('device_hash', '!=', $deviceHash)
            ->whereNull('logged_out_at')
            ->where('last_seen_at', '>=', now()->subMinutes(15))
            ->distinct()
            ->count('device_hash');
        $sharedAccountsCount = DeviceLogin::where('device_hash', $deviceHash)
            ->where('user_id', '!=', $user->id)
            ->where('last_seen_at', '>=', now()->subDays(90))
            ->distinct()
            ->count('user_id');
        $recentDeviceChanges = DeviceLogin::where('user_id', $user->id)
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->distinct()
            ->count('device_hash');
        [$riskScore, $riskReasons] = $this->riskSignal(
            $isNewDevice,
            $simultaneousDevicesCount,
            $sharedAccountsCount,
            $recentDeviceChanges
        );

        $login = DeviceLogin::create([
            'user_id' => $user->id,
            'device_hash' => $deviceHash,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'approx_location' => $this->approximateLocation($request),
            'user_agent' => $userAgent ?: null,
            'device_label' => $this->deviceLabel($userAgent),
            'is_new_device' => $isNewDevice,
            'risk_score' => $riskScore,
            'risk_reasons' => $riskReasons,
            'simultaneous_devices_count' => $simultaneousDevicesCount,
            'shared_accounts_count' => $sharedAccountsCount,
            'logged_in_at' => now(),
            'last_seen_at' => now(),
        ]);

        if ($isNewDevice && $previousDeviceLogins) {
            $user->notify(new NewDeviceLoginNotification($login));
        }

        return $login;
    }

    private function riskSignal(bool $isNewDevice, int $simultaneousDevicesCount, int $sharedAccountsCount, int $recentDeviceChanges): array
    {
        $score = 0;
        $reasons = [];

        if ($sharedAccountsCount > 0) {
            $score += 60;
            $reasons[] = 'Este navegador/dispositivo ya fue usado por otra cuenta.';
        }

        if ($simultaneousDevicesCount > 0) {
            $score += 35;
            $reasons[] = 'La misma cuenta tuvo actividad reciente en otro dispositivo.';
        }

        if ($isNewDevice) {
            $score += 15;
            $reasons[] = 'Inicio de sesión desde un dispositivo nuevo para esta cuenta.';
        }

        if ($recentDeviceChanges >= 3) {
            $score += 20;
            $reasons[] = 'La cuenta cambió de dispositivo varias veces en los últimos 7 días.';
        }

        return [min($score, 100), $reasons];
    }

    private function approximateLocation(Request $request): string
    {
        $ip = $request->ip();

        if (! $ip) {
            return 'Ubicación no disponible';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'Red local/privada';
        }

        $country = $request->headers->get('CF-IPCountry') ?: $request->headers->get('X-Appengine-Country');
        $city = $request->headers->get('CF-IPCity') ?: $request->headers->get('X-Appengine-City');

        if ($city || $country) {
            return trim(collect([$city, $country])->filter()->join(', '));
        }

        return 'IP pública (geolocalización pendiente)';
    }

    private function deviceLabel(string $userAgent): string
    {
        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Dispositivo desconocido',
        };

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/'), str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Navegador desconocido',
        };

        return "{$platform} · {$browser}";
    }
}
