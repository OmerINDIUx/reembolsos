<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Services\GoogleUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GoogleUserProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_active_user_with_a_verified_google_identity(): void
    {
        $user = app(GoogleUserProvisioner::class)->provision([
            'sub' => 'google-1',
            'name' => 'Nueva Persona',
            'email' => ' PERSONA@CONSTRULERMA.COM ',
            'email_verified' => true,
        ]);

        $this->assertSame('persona@construlerma.com', $user->email);
        $this->assertSame('persona@construlerma.com', $user->email_normalized);
        $this->assertSame('google-1', $user->google_id);
        $this->assertSame('active', $user->status);
        $this->assertSame('Usuario General', $user->profile->display_name);
    }

    public function test_pending_user_is_linked_without_changing_its_profile(): void
    {
        $profile = Profile::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        $user = User::factory()->create([
            'name' => 'Nombre previo',
            'email' => 'pendiente@archandel.com',
            'email_normalized' => 'pendiente@archandel.com',
            'profile_id' => $profile->id,
            'status' => 'pending',
            'google_id' => null,
            'invitation_token' => 'invitation-token',
        ]);

        $linked = app(GoogleUserProvisioner::class)->provision([
            'sub' => 'google-pending',
            'name' => 'Nombre Google',
            'email' => 'PENDIENTE@ARCHANDEL.COM',
            'email_verified' => true,
        ]);

        $this->assertSame($user->id, $linked->id);
        $this->assertSame($profile->id, $linked->profile_id);
        $this->assertSame('active', $linked->status);
        $this->assertSame('google-pending', $linked->google_id);
        $this->assertNull($linked->invitation_token);
        $this->assertTrue($linked->isRegistered());
        $this->assertSame('Nombre Google', $linked->name);
    }

    public function test_returning_google_login_preserves_the_confirmed_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Nombre confirmado en reembolsos',
            'email' => 'confirmado@archandel.com',
            'email_normalized' => 'confirmado@archandel.com',
            'google_id' => 'google-confirmed',
            'personal_info_confirmed_at' => now(),
            'status' => 'active',
        ]);

        $result = app(GoogleUserProvisioner::class)->provision([
            'sub' => 'google-confirmed',
            'name' => 'Nombre distinto de Google',
            'email' => 'confirmado@archandel.com',
            'email_verified' => true,
        ]);

        $this->assertSame($user->id, $result->id);
        $this->assertSame('Nombre confirmado en reembolsos', $result->name);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);

        app(GoogleUserProvisioner::class)->provision([
            'sub' => 'google-unverified',
            'name' => 'Sin verificar',
            'email' => 'persona@archandel.com',
            'email_verified' => false,
        ]);
    }
}
