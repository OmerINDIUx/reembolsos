<?php

namespace App\Services;

use App\Models\DeviceLogin;
use App\Models\LoginSecurityChallenge;
use App\Models\User;
use App\Notifications\HighRiskLoginCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginSecurityChallengeService
{
    public const SESSION_KEY = 'login_security_challenge_id';
    public const HIGH_RISK_THRESHOLD = 50;
    public const MAX_ATTEMPTS = 5;
    public const VERIFICATION_RISK_REDUCTION = 30;

    public function shouldChallenge(?DeviceLogin $deviceLogin): bool
    {
        return $deviceLogin && (int) $deviceLogin->risk_score >= self::HIGH_RISK_THRESHOLD;
    }

    public function create(User $user, ?DeviceLogin $deviceLogin, Request $request): LoginSecurityChallenge
    {
        LoginSecurityChallenge::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $challenge = LoginSecurityChallenge::create([
            'user_id' => $user->id,
            'device_login_id' => $deviceLogin?->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        $user->notify(new HighRiskLoginCodeNotification($code, $deviceLogin));

        return $challenge;
    }

    public function verify(LoginSecurityChallenge $challenge, string $code): void
    {
        if ($challenge->isVerified()) {
            throw ValidationException::withMessages([
                'code' => 'Este código ya fue usado. Solicita uno nuevo.',
            ]);
        }

        if ($challenge->isExpired()) {
            throw ValidationException::withMessages([
                'code' => 'El código venció. Solicita uno nuevo.',
            ]);
        }

        if ($challenge->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Se agotaron los intentos para este código. Solicita uno nuevo.',
            ]);
        }

        $challenge->increment('attempts');

        if (! Hash::check($code, $challenge->code_hash)) {
            throw ValidationException::withMessages([
                'code' => 'El código no es correcto.',
            ]);
        }

        $challenge->forceFill([
            'verified_at' => now(),
        ])->save();

        if ($challenge->deviceLogin) {
            $this->reduceRiskAfterVerification($challenge->deviceLogin);
        }
    }

    private function reduceRiskAfterVerification(DeviceLogin $deviceLogin): void
    {
        $currentRisk = (int) $deviceLogin->risk_score;
        $reducedRisk = max(0, $currentRisk - self::VERIFICATION_RISK_REDUCTION);
        $reasons = collect($deviceLogin->risk_reasons ?? [])
            ->reject(fn (string $reason) => $reason === 'La verificación adicional fue completada y el riesgo residual bajó.')
            ->values()
            ->all();

        if ($reducedRisk < $currentRisk) {
            $reasons[] = 'La verificación adicional fue completada y el riesgo residual bajó.';
        }

        $deviceLogin->forceFill([
            'risk_score' => $reducedRisk,
            'risk_reasons' => $reasons,
            'last_seen_at' => now(),
        ])->save();
    }
}
