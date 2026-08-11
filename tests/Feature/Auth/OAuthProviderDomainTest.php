<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthProviderDomainTest extends TestCase
{
    public function test_microsoft_rejects_a_construlerma_account(): void
    {
        config()->set('services.microsoft', [
            'client_id' => 'microsoft-client',
            'client_secret' => 'microsoft-secret',
            'tenant' => 'common',
            'redirect' => 'http://localhost/auth/microsoft/callback',
            'allowed_domains' => ['grupoindi.com'],
        ]);

        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
            'graph.microsoft.com/*' => Http::response([
                'id' => 'entra-1',
                'displayName' => 'Persona',
                'mail' => 'persona@construlerma.com',
            ]),
        ]);

        $response = $this->withSession(['microsoft_oauth_state' => 'valid-state'])
            ->get('/auth/microsoft/callback?state=valid-state&code=code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Microsoft solo está autorizado para cuentas @grupoindi.com.',
        ]);
        $this->assertGuest();
    }

    public function test_google_rejects_a_grupoindi_account(): void
    {
        config()->set('services.google', [
            'client_id' => 'google-client',
            'client_secret' => 'google-secret',
            'redirect' => 'http://localhost/auth/google/callback',
            'allowed_domains' => ['construlerma.com', 'archandel.com'],
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-1',
                'name' => 'Persona',
                'email' => 'persona@grupoindi.com',
                'email_verified' => true,
            ]),
        ]);

        $response = $this->withSession(['google_oauth_state' => 'valid-state'])
            ->get('/auth/google/callback?state=valid-state&code=code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Google solo está autorizado para cuentas @construlerma.com y @archandel.com.',
        ]);
        $this->assertGuest();
    }

    public function test_google_redirect_uses_oauth_state_and_requested_scopes(): void
    {
        config()->set('services.google', [
            'client_id' => 'google-client',
            'client_secret' => 'google-secret',
            'redirect' => 'http://localhost/auth/google/callback',
            'allowed_domains' => ['construlerma.com', 'archandel.com'],
        ]);

        $response = $this->get('/auth/google');

        $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth?');
        $response->assertSessionHas('google_oauth_state');
        $this->assertStringContainsString('scope=openid+profile+email', $response->headers->get('Location'));
    }
}
