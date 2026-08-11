<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DeviceLogin;

use App\Models\User;
use App\Services\DeviceLoginService;
use App\Services\MicrosoftUserProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        MicrosoftUserProvisioner $provisioner,
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

        $identityEmail = strtolower(trim((string) ($identity['mail'] ?? $identity['userPrincipalName'] ?? '')));
        if (! filter_var($identityEmail, FILTER_VALIDATE_EMAIL) || ! $this->isAuthorizedMicrosoftEmail($identityEmail)) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta de Microsoft no pertenece a un dominio autorizado.']);
        }

        try {
            $user = $provisioner->provision($identity);
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('login')->withErrors(['email' => $exception->getMessage()]);
        }

        if ($user->isDisabled()) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está deshabilitada. Contacta a un administrador.']);
        }
 else {
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

        return redirect()->intended(route('panel', absolute: false));
    }

    private function isAuthorizedMicrosoftEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        return in_array($domain, config('services.microsoft.allowed_domains', []), true);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        DeviceLoginService $deviceLoginService,
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $deviceLogin = $deviceLoginService->record($user, $request);
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