<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DeviceLogin;
use App\Models\Profile;
use App\Models\User;
use App\Services\DeviceLoginService;
use App\Services\LoginSecurityChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    public function redirectToMicrosoft(Request $request): RedirectResponse
    {
        $config = config('services.microsoft');
        if (blank($config['client_id']) || blank($config['redirect'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'El acceso con Microsoft aún no está configurado.',
            ]);
        }

        $state = Str::random(64);
        $request->session()->put('microsoft_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect'],
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state,
        ]);

        return redirect('https://login.microsoftonline.com/'.rawurlencode($config['tenant']).'/oauth2/v2.0/authorize?'.$query);
    }

    public function handleMicrosoftCallback(
        Request $request,
        DeviceLoginService $deviceLoginService,
        LoginSecurityChallengeService $challengeService
    ): RedirectResponse {
        $expectedState = $request->session()->pull('microsoft_oauth_state');
        if (blank($expectedState) || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect()->route('login')->withErrors(['email' => 'La sesión de Microsoft expiró. Intenta nuevamente.']);
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'email' => $request->query('error_description', 'No fue posible iniciar sesión con Microsoft.'),
            ]);
        }

        if (! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['email' => 'Microsoft no devolvió un código de acceso válido.']);
        }

        try {
            $config = config('services.microsoft');
            $tokenResponse = Http::asForm()->post(
                'https://login.microsoftonline.com/'.rawurlencode($config['tenant']).'/oauth2/v2.0/token',
                [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'code' => $request->query('code'),
                    'redirect_uri' => $config['redirect'],
                    'grant_type' => 'authorization_code',
                    'scope' => 'openid profile email User.Read',
                ]
            )->throw()->json();

            $identity = Http::withToken($tokenResponse['access_token'])
                ->get('https://graph.microsoft.com/v1.0/me?$select=id,displayName,mail,userPrincipalName')
                ->throw()->json();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'email' => 'No fue posible validar tu cuenta de Microsoft. Intenta nuevamente.',
            ]);
        }

        $email = Str::lower(trim((string) ($identity['mail'] ?? $identity['userPrincipalName'] ?? '')));
        if (blank($email) || blank($identity['id'] ?? null)) {
            return redirect()->route('login')->withErrors(['email' => 'Microsoft no proporcionó un correo válido para tu cuenta.']);
        }

        $user = User::where('microsoft_id', $identity['id'])->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            $profile = Profile::firstOrCreate(
                ['name' => 'user'],
                ['display_name' => 'Usuario General']
            );

            $user = User::create([
                'name' => trim((string) ($identity['displayName'] ?? $email)),
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
                'role' => 'user',
                'profile_id' => $profile->id,
                'microsoft_id' => $identity['id'],
            ]);
        } else {
            $updates = ['email_verified_at' => $user->email_verified_at ?: now()];
            if (blank($user->microsoft_id)) {
                $updates['microsoft_id'] = $identity['id'];
            }
            $user->update($updates);
        }

        if ($user->isBlocked()) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está bloqueada. Contacta a un administrador.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $deviceLogin = $deviceLoginService->record($user, $request);
        if ($deviceLogin) {
            $request->session()->put('device_login_id', $deviceLogin->id);
        }

        if ($challengeService->shouldChallenge($deviceLogin)) {
            $challenge = $challengeService->create($user, $deviceLogin, $request);
            Auth::guard('web')->logout();
            $request->session()->forget('device_login_id');
            $request->session()->put(LoginSecurityChallengeService::SESSION_KEY, $challenge->id);
            $request->session()->put('login_security_remember', true);

            return redirect()->route('login.security_code.show')
                ->with('status', 'Por seguridad, enviamos un código a tu correo para completar el acceso.');
        }

        return redirect()->intended(route('panel', absolute: false));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        DeviceLoginService $deviceLoginService,
        LoginSecurityChallengeService $challengeService
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $deviceLogin = $deviceLoginService->record($user, $request);
        if ($deviceLogin) {
            $request->session()->put('device_login_id', $deviceLogin->id);
        }

        if ($challengeService->shouldChallenge($deviceLogin)) {
            $challenge = $challengeService->create($user, $deviceLogin, $request);

            Auth::guard('web')->logout();

            $request->session()->forget('device_login_id');
            $request->session()->put(LoginSecurityChallengeService::SESSION_KEY, $challenge->id);
            $request->session()->put('login_security_remember', $request->boolean('remember'));

            return redirect()
                ->route('login.security_code.show')
                ->with('status', 'Por seguridad, enviamos un código a tu correo para completar el acceso.');
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
