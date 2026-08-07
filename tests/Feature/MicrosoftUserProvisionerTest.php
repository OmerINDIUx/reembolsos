<?php

namespace Tests\Feature;

use App\Models\CostCenter;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Services\MicrosoftUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class MicrosoftUserProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_active_user_with_normalized_identity(): void
    {
        $user = app(MicrosoftUserProvisioner::class)->provision([
            'id' => 'entra-1',
            'displayName' => 'Nueva Persona',
            'mail' => '  PERSONA@EMPRESA.COM ',
        ]);

        $this->assertSame('persona@empresa.com', $user->email);
        $this->assertSame('persona@empresa.com', $user->email_normalized);
        $this->assertSame('entra-1', $user->microsoft_id);
        $this->assertSame('active', $user->status);
        $this->assertSame('Usuario General', $user->profile->display_name);
    }

    public function test_pending_user_is_linked_and_assignments_are_preserved(): void
    {
        $profile = Profile::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        $permission = Permission::create(['name' => 'users.view', 'display_name' => 'Ver usuarios', 'module' => 'users']);
        $costCenter = CostCenter::create(['code' => 'CC-1', 'name' => 'Centro 1', 'is_active' => true]);
        $user = User::factory()->create([
            'name' => 'Nombre previo',
            'email' => 'pendiente@empresa.com',
            'email_normalized' => 'pendiente@empresa.com',
            'profile_id' => $profile->id,
            'status' => 'pending',
            'microsoft_id' => null,
        ]);
        $user->authorizedCostCenters()->attach($costCenter);
        $user->permissions()->attach($permission);

        $linked = app(MicrosoftUserProvisioner::class)->provision([
            'id' => 'entra-pending',
            'displayName' => 'Nombre Microsoft',
            'userPrincipalName' => ' PENDIENTE@EMPRESA.COM ',
        ]);

        $this->assertSame($user->id, $linked->id);
        $this->assertSame('active', $linked->status);
        $this->assertSame('entra-pending', $linked->microsoft_id);
        $this->assertTrue($linked->authorizedCostCenters()->whereKey($costCenter->id)->exists());
        $this->assertTrue($linked->permissions()->whereKey($permission->id)->exists());
    }

    public function test_disabled_user_is_not_activated(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@empresa.com',
            'email_normalized' => 'disabled@empresa.com',
            'status' => 'disabled',
        ]);

        $result = app(MicrosoftUserProvisioner::class)->provision([
            'id' => 'entra-disabled',
            'displayName' => 'Disabled',
            'mail' => 'disabled@empresa.com',
        ]);

        $this->assertSame($user->id, $result->id);
        $this->assertSame('disabled', $result->status);
        $this->assertNull($result->microsoft_id);
    }

    public function test_conflicting_microsoft_identity_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'one@empresa.com',
            'email_normalized' => 'one@empresa.com',
            'microsoft_id' => 'entra-shared',
            'status' => 'active',
        ]);
        User::factory()->create([
            'email' => 'two@empresa.com',
            'email_normalized' => 'two@empresa.com',
            'status' => 'pending',
        ]);

        $this->expectException(RuntimeException::class);
        app(MicrosoftUserProvisioner::class)->provision([
            'id' => 'entra-shared',
            'displayName' => 'Two',
            'mail' => 'two@empresa.com',
        ]);
    }
}
