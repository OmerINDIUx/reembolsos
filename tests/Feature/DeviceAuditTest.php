<?php

namespace Tests\Feature;

use App\Models\DeviceLogin;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_the_device(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/126.0')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect('/panel');
        $this->assertDatabaseHas('device_logins', [
            'user_id' => $user->id,
            'device_label' => 'Windows · Chrome',
        ]);
        $this->assertNotNull($response->getCookie('reembolsos_device'));
    }

    public function test_users_with_the_device_audit_permission_can_view_the_module(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $profile = Profile::create([
            'name' => 'auditor-de-dispositivos',
            'display_name' => 'Auditor de dispositivos',
        ]);
        $profile->permissions()->sync([
            Permission::where('name', 'dashboard.device_audit')->firstOrFail()->id,
        ]);
        $auditor = User::factory()->create(['profile_id' => $profile->id]);

        $this->actingAs($user)
            ->get(route('admin.device-audit.index'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->get(route('admin.device-audit.index'))
            ->assertOk()
            ->assertSee('Auditoría de accesos y dispositivos');

        $this->actingAs($admin)
            ->get(route('admin.device-audit.index'))
            ->assertOk()
            ->assertSee('Auditoría de accesos y dispositivos');
    }

    public function test_panel_identifies_a_device_used_by_several_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $deviceHash = hash('sha256', 'shared-browser');

        foreach ([$firstUser, $secondUser] as $user) {
            DeviceLogin::create([
                'user_id' => $user->id,
                'device_hash' => $deviceHash,
                'device_label' => 'Windows · Chrome',
                'logged_in_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.device-audit.index'))
            ->assertOk()
            ->assertSee($firstUser->email)
            ->assertSee($secondUser->email);
    }

    public function test_admin_can_block_and_unblock_an_account_with_an_audited_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.device-audit.block', $user), [
                'reason' => 'credential_sharing',
            ])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->isBlocked());
        $this->assertSame($admin->id, $user->blocked_by);
        $this->assertDatabaseHas('account_block_events', [
            'user_id' => $user->id,
            'actor_id' => $admin->id,
            'action' => 'blocked',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.device-audit.unblock', $user))
            ->assertSessionHas('success');

        $this->assertFalse($user->refresh()->isBlocked());
    }

    public function test_blocked_user_cannot_log_in_and_sees_the_reason(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'blocked_at' => now(),
            'blocked_reason_code' => 'credential_sharing',
            'blocked_reason_message' => 'Uso indebido por compartir usuario o contraseña.',
        ])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
