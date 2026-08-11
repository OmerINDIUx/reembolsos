<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftUserProvisioner
{
    public function provision(array $identity): User
    {
        $microsoftId = trim((string) ($identity['id'] ?? ''));
        $email = Str::lower(trim((string) ($identity['mail'] ?? $identity['userPrincipalName'] ?? '')));

        if ($microsoftId === '' || $email === '') {
            throw new RuntimeException('Microsoft no proporcionó una identidad completa.');
        }

        return DB::transaction(function () use ($identity, $microsoftId, $email): User {
            $byMicrosoft = User::where('microsoft_id', $microsoftId)->lockForUpdate()->first();
            $byEmail = User::where('email_normalized', $email)
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($byMicrosoft && $byEmail && $byMicrosoft->id !== $byEmail->id) {
                throw new RuntimeException('La identidad de Microsoft ya está vinculada a otro usuario.');
            }

            $user = $byMicrosoft ?: $byEmail;
            $name = trim((string) ($identity['displayName'] ?? $email));

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
                        'microsoft_id' => $microsoftId,
                        'status' => 'active',
                    ]);
                } catch (QueryException $exception) {
                    $existing = User::where('email_normalized', $email)->orWhere('microsoft_id', $microsoftId)->first();
                    if ($existing) return $existing;
                    throw $exception;
                }
            }

            if ($user->microsoft_id && $user->microsoft_id !== $microsoftId) {
                throw new RuntimeException('El correo ya está vinculado a otra identidad de Microsoft.');
            }

            if ($user->isDisabled()) return $user;

            $user->forceFill([
                'name' => $name ?: $user->name,
                'email' => $email,
                'email_normalized' => $email,
                'microsoft_id' => $microsoftId,
                'email_verified_at' => $user->email_verified_at ?: now(),
                'status' => 'active',
            ])->save();

            return $user->fresh();
        });
    }
}
