<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Si la contraseña utilizada es la genérica, marcar para cambio obligatorio
        if ($request->password === 'S20hg00146') {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->must_change_password = true;
            $user->save();
        }

        // Si el usuario tiene marcado el cambio obligatorio, redirigir a la pantalla de cambio
        if (Auth::user()->must_change_password) {
            return redirect()->route('password.force_change');
        }

        return redirect()->intended(route('reimbursements.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
