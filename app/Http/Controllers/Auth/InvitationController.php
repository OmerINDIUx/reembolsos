<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance form.
     */
    public function accept($token)
    {
        $user = User::where('invitation_token', $token)->firstOrFail();

        return view('auth.confirm-invitation', compact('user', 'token'));
    }

    /**
     * Complete the registration process.
     */
    public function complete(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255', 'regex:/^\s*\S+(?:\s+\S+){2,}\s*$/u'],
            'rfc' => ['required', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/i'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'bank_name' => ['required', 'string', 'max:255'],
            'clabe' => ['required', 'string', 'size:18', 'regex:/^[0-9]+$/'],
            'personal_info_confirmed' => ['accepted'],
        ], [
            'name.regex' => 'Escribe tu nombre completo con nombre y dos apellidos.',
            'rfc.regex' => 'El RFC debe tener un formato válido de 12 o 13 caracteres.',
            'personal_info_confirmed.accepted' => 'Debes confirmar que tu información personal es correcta.',
        ]);

        $user = User::where('invitation_token', $request->token)->firstOrFail();

        $user->forceFill([
            'name' => trim($request->name),
            'password' => Hash::make($request->password),
            'bank_name' => strtoupper(trim($request->bank_name)),
            'clabe' => $request->clabe,
            'rfc' => strtoupper(trim($request->rfc)),
            'personal_info_confirmed_at' => now(),
            'invitation_token' => null,
            'invitation_sent_at' => null,
        ])->save();

        Auth::login($user);

        return redirect()->route('panel')->with('success', 'Tu cuenta ha sido activada correctamente. ¡Bienvenido!');
    }
}
