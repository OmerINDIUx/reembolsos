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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'bank_name' => ['required', 'string', 'max:255'],
            'clabe' => ['required', 'string', 'size:18', 'regex:/^[0-9]+$/'],
        ]);

        $user = User::where('invitation_token', $request->token)->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($request->password),
            'bank_name' => $request->bank_name,
            'clabe' => $request->clabe,
            'invitation_token' => null,
            'invitation_sent_at' => null,
        ])->save();

        Auth::login($user);

        return redirect()->route('panel')->with('success', 'Tu cuenta ha sido activada correctamente. ¡Bienvenido!');
    }
}
