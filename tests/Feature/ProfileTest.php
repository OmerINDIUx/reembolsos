<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertDontSee('Delete Account');
        $this->assertFalse(Route::has('profile.destroy'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_account_deletion_is_not_available_for_any_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile')
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }

    public function test_duplicate_profile_name_returns_a_validation_error(): void
    {
        Profile::create([
            'name' => 'auditor-de-finanzas',
            'display_name' => 'Auditor de Finanzas',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('profiles.store'), [
                'display_name' => 'Auditor de Finanzas',
            ])
            ->assertSessionHasErrors('display_name');

        $this->assertSame(1, Profile::where('name', 'auditor-de-finanzas')->count());
    }
}
