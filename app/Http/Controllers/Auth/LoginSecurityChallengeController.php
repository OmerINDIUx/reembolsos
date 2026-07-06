<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginSecurityChallenge;
use App\Services\LoginSecurityChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginSecurityChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $challenge = $this->pendingChallenge($request);

        if (! $challenge) {
            return redirect()->route('login')->withErrors([
                'email' => 'No hay una verificación pendiente. Inicia sesión nuevamente.',
            ]);
        }

        return view('auth.security-code', compact('challenge'));
    }

    public function verify(Request $request, LoginSecurityChallengeService $service): RedirectResponse
    {
        $challenge = $this->pendingChallenge($request);

        if (! $challenge) {
            return redirect()->route('login')->withErrors([
                'email' => 'No hay una verificación pendiente. Inicia sesión nuevamente.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $service->verify($challenge, $validated['code']);

        Auth::login($challenge->user, (bool) $request->session()->pull('login_security_remember', false));
        $request->session()->regenerate();
        $request->session()->forget(LoginSecurityChallengeService::SESSION_KEY);

        if ($challenge->device_login_id) {
            $request->session()->put('device_login_id', $challenge->device_login_id);
            $challenge->deviceLogin()->update([
                'session_id' => $request->session()->getId(),
                'last_seen_at' => now(),
            ]);
        }

        return redirect()->intended(route('panel', absolute: false));
    }

    public function resend(Request $request, LoginSecurityChallengeService $service): RedirectResponse
    {
        $challenge = $this->pendingChallenge($request);

        if (! $challenge) {
            return redirect()->route('login')->withErrors([
                'email' => 'No hay una verificación pendiente. Inicia sesión nuevamente.',
            ]);
        }

        $newChallenge = $service->create($challenge->user, $challenge->deviceLogin, $request);
        $request->session()->put(LoginSecurityChallengeService::SESSION_KEY, $newChallenge->id);

        return back()->with('status', 'Te enviamos un nuevo código de seguridad.');
    }

    private function pendingChallenge(Request $request): ?LoginSecurityChallenge
    {
        $challengeId = $request->session()->get(LoginSecurityChallengeService::SESSION_KEY);

        if (! $challengeId) {
            return null;
        }

        return LoginSecurityChallenge::with(['user', 'deviceLogin'])->find($challengeId);
    }
}
