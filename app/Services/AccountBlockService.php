<?php

namespace App\Services;

use App\Models\AccountBlockEvent;
use App\Models\User;
use App\Support\AccountBlockReasons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountBlockService
{
    public function block(User $target, User $actor, string $reasonCode, Request $request): void
    {
        if ($target->is($actor)) {
            throw ValidationException::withMessages([
                'user' => 'No puedes bloquear tu propia cuenta administrativa.',
            ]);
        }


        if ($target->isBlocked()) {
            throw ValidationException::withMessages([
                'user' => 'Esta cuenta ya se encuentra bloqueada.',
            ]);
        }

        $message = AccountBlockReasons::message($reasonCode);
        if (! $message) {
            throw ValidationException::withMessages(['reason' => 'Selecciona un motivo válido.']);
        }

        DB::transaction(function () use ($target, $actor, $reasonCode, $message, $request) {
            $target->forceFill([
                'blocked_at' => now(),
                'blocked_reason_code' => $reasonCode,
                'blocked_reason_message' => $message,
                'blocked_by' => $actor->id,
                'remember_token' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $target->id)->delete();

            AccountBlockEvent::create([
                'user_id' => $target->id,
                'actor_id' => $actor->id,
                'action' => 'blocked',
                'reason_code' => $reasonCode,
                'reason_message' => $message,
                'ip_address' => $request->ip(),
            ]);
        });
    }

    public function unblock(User $target, User $actor, Request $request): void
    {
        if (! $target->isBlocked()) {
            throw ValidationException::withMessages([
                'user' => 'Esta cuenta ya se encuentra activa.',
            ]);
        }

        DB::transaction(function () use ($target, $actor, $request) {
            $previousCode = $target->blocked_reason_code;
            $previousMessage = $target->blocked_reason_message;

            $target->forceFill([
                'blocked_at' => null,
                'blocked_reason_code' => null,
                'blocked_reason_message' => null,
                'blocked_by' => null,
            ])->save();

            AccountBlockEvent::create([
                'user_id' => $target->id,
                'actor_id' => $actor->id,
                'action' => 'unblocked',
                'reason_code' => $previousCode,
                'reason_message' => $previousMessage,
                'ip_address' => $request->ip(),
            ]);
        });
    }
}
