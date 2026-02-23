<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChangeController extends Controller
{
    /**
     * Display the force password change view.
     */
    public function show()
    {
        return view('auth.force-password-change');
    }

    /**
     * Update the user's password.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Contraseña actualizada correctamente. Bienvenido al sistema.');
    }
}
