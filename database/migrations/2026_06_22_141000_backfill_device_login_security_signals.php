<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_logins') || ! Schema::hasColumn('device_logins', 'risk_score')) {
            return;
        }

        DB::table('device_logins')
            ->orderBy('id')
            ->chunkById(200, function ($logins) {
                foreach ($logins as $login) {
                    $isNewDevice = ! DB::table('device_logins')
                        ->where('user_id', $login->user_id)
                        ->where('device_hash', $login->device_hash)
                        ->where('id', '<', $login->id)
                        ->exists();

                    $sharedAccountsCount = DB::table('device_logins')
                        ->where('device_hash', $login->device_hash)
                        ->where('user_id', '!=', $login->user_id)
                        ->where('last_seen_at', '>=', now()->subDays(90))
                        ->distinct()
                        ->count('user_id');

                    $simultaneousDevicesCount = DB::table('device_logins')
                        ->where('user_id', $login->user_id)
                        ->where('device_hash', '!=', $login->device_hash)
                        ->whereBetween('last_seen_at', [
                            Carbon::parse($login->logged_in_at)->subMinutes(15),
                            Carbon::parse($login->logged_in_at)->addMinutes(15),
                        ])
                        ->distinct()
                        ->count('device_hash');

                    $recentDeviceChanges = DB::table('device_logins')
                        ->where('user_id', $login->user_id)
                        ->whereBetween('last_seen_at', [
                            Carbon::parse($login->last_seen_at ?? $login->logged_in_at)->subDays(7),
                            Carbon::parse($login->last_seen_at ?? $login->logged_in_at),
                        ])
                        ->distinct()
                        ->count('device_hash');

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
                        $reasons[] = 'La cuenta cambió de dispositivo varias veces en un periodo corto.';
                    }

                    DB::table('device_logins')
                        ->where('id', $login->id)
                        ->update([
                            'is_new_device' => $isNewDevice,
                            'shared_accounts_count' => $sharedAccountsCount,
                            'simultaneous_devices_count' => $simultaneousDevicesCount,
                            'risk_score' => min($score, 100),
                            'risk_reasons' => $reasons ? json_encode($reasons, JSON_UNESCAPED_UNICODE) : null,
                            'approx_location' => $login->approx_location ?: $this->approximateLocation($login->ip_address),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No destructive rollback: these values are derived audit metadata.
    }

    private function approximateLocation(?string $ip): string
    {
        if (! $ip) {
            return 'Ubicación no disponible';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'Red local/privada';
        }

        return 'IP pública (geolocalización pendiente)';
    }
};
