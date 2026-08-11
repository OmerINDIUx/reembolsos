<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleUserProvisioner
{
    public function provision(array $identity): User
    {
        $googleId = trim((string) ($identity['sub'] ?? ''));
        $email = Str::lower(trim((string) ($identity['email'] ?? '')));

        if ($googleId === '' || $email === '' || ! ($identity['email_verified'] ?? false)) {
            throw new RuntimeException('Google no proporcionó una identidad de correo verificada.');
        }

        return DB::transaction(function () use ($identity, $googleId, $email): User {
            $byGoogle = User::where('google_id', $googleId)->lockForUpdate()->first();
            $byEmail = User::where('email_normalized', $email)
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($byGoogle && $byEmail && $byGoogle->id !== $byEmail->id) {
                throw new RuntimeException('La identidad de Google ya está vinculada a otro usuario.');
            }

            $user = $byGoogle ?: $byEmail;
            $name = trim((string) ($identity['name'] ?? $email));

            if (! $user) {
                $profile = Profile::firstOrCreate(['name' => 'user'], ['display_name' => 'Usuario General']);

                try {
                    return User::create([
                        'name' => $name,
                        'email' => $email,
                        'email_normalized' => $email,
                        'password' => Hash::make(Str::random(64)),
                        'email_verified_at' => now(),
                        'role' => 'user',
                        'profile_id' => $profile->id,
                        'google_id' => $googleId,
                        'status' => 'active',
                    ]);
                } catch (QueryException $exception) {
                    $existing = User::where('email_normalized', $email)->orWhere('google_id', $googleId)->first();
                    if ($existing) {
                        return $existing;
                    }
                    throw $exception;
                }
            }

            if ($user->google_id && $user->google_id !== $googleId) {
                throw new RuntimeException('El correo ya está vinculado a otra identidad de Google.');
            }

            if ($user->isDisabled()) {
                return $user;
            }

            $isFirstGoogleLogin = blank($user->google_id);

            $user->forceFill([
                'name' => $isFirstGoogleLogin ? $name : $user->name,
                'email' => $email,
                'email_normalized' => $email,
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?: now(),
                'status' => 'active',
            ])->save();

            return $user->fresh();
        });
    }
}
