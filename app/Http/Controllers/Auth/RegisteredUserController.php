<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^\s*\S+(?:\s+\S+){2,}\s*$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'rfc' => ['required', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/i'],
            'bank_name' => ['required', 'string', 'max:255'],
            'clabe' => ['required', 'string', 'size:18', 'regex:/^[0-9]+$/'],
            'personal_info_confirmed' => ['accepted'],
        ], [
            'name.regex' => 'Escribe tu nombre completo con nombre y dos apellidos.',
            'rfc.regex' => 'El RFC debe tener un formato válido de 12 o 13 caracteres.',
            'personal_info_confirmed.accepted' => 'Debes confirmar que tu información personal es correcta.',
        ]);

        $user = User::create([
            'name' => trim($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'bank_name' => strtoupper(trim($request->bank_name)),
            'clabe' => $request->clabe,
            'rfc' => strtoupper(trim($request->rfc)),
            'personal_info_confirmed_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('panel', absolute: false));
    }
}
