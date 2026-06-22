<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DeviceLogin;
use App\Services\DeviceLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
    public function store(LoginRequest $request, DeviceLoginService $deviceLoginService): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $deviceLogin = $deviceLoginService->record($request->user(), $request);
        if ($deviceLogin) {
            $request->session()->put('device_login_id', $deviceLogin->id);
        }

        return redirect()->intended(route('panel', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $deviceLoginId = $request->session()->get('device_login_id');
        if ($deviceLoginId && Schema::hasTable('device_logins')) {
            DeviceLogin::whereKey($deviceLoginId)
                ->where('user_id', $request->user()?->id)
                ->update([
                    'last_seen_at' => now(),
                    'logged_out_at' => now(),
                ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
