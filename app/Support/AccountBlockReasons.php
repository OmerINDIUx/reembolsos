<?php

namespace App\Support;

class AccountBlockReasons
{
    public static function all(): array
    {
        return [
            'credential_sharing' => 'Uso indebido por compartir usuario o contraseña.',
            'multiple_people' => 'La cuenta parece estar siendo utilizada por varias personas.',
            'unauthorized_device' => 'Se detectaron accesos reiterados desde dispositivos no autorizados.',
            'suspicious_activity' => 'Se detectó actividad inusual o potencialmente riesgosa.',
            'identity_misuse' => 'La cuenta fue utilizada para actuar en nombre de otra persona.',
            'policy_violation' => 'Incumplimiento de las políticas internas de acceso al sistema.',
            'data_misuse' => 'Uso inadecuado de información o funciones del sistema.',
            'verification_failed' => 'No fue posible validar la identidad del titular de la cuenta.',
            'repeated_warning' => 'Reincidencia después de avisos previos por uso indebido.',
            'administrative_review' => 'Cuenta suspendida temporalmente para revisión administrativa.',
        ];
    }

    public static function message(string $code): ?string
    {
        return self::all()[$code] ?? null;
    }
}
