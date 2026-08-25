<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostCenterPermissionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_profile_with_all_cost_center_permissions_can_use_the_module(): void
    {
        $user = $this->createUserWithPermissions([
            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.edit',
            'cost_centers.delete',
        ]);
        $costCenter = $this->createCostCenter();

        $this->actingAs($user)
            ->get(route('cost_centers.index'))
            ->assertOk()
            ->assertSee($costCenter->name)
            ->assertSee('Nuevo Centro de Costos')
            ->assertSee('Editar')
            ->assertSee('Desactivar');

        $this->actingAs($user)->get(route('cost_centers.create'))->assertOk();
        $this->actingAs($user)->get(route('cost_centers.edit', $costCenter))->assertOk();
        $this->actingAs($user)->get(route('cost_centers.deactivation', $costCenter))->assertOk();
    }

    public function test_view_only_custom_profile_sees_records_but_cannot_modify_them(): void
    {
        $user = $this->createUserWithPermissions(['cost_centers.view']);
        $costCenter = $this->createCostCenter();

        $this->actingAs($user)
            ->get(route('cost_centers.index'))
            ->assertOk()
            ->assertSee($costCenter->name)
            ->assertDontSee('Nuevo Centro de Costos')
            ->assertDontSee('Desactivar');

        $this->actingAs($user)->get(route('cost_centers.create'))->assertForbidden();
        $this->actingAs($user)->get(route('cost_centers.edit', $costCenter))->assertForbidden();
        $this->actingAs($user)->get(route('cost_centers.deactivation', $costCenter))->assertForbidden();
    }

    private function createUserWithPermissions(array $permissionNames): User
    {
        $profile = Profile::create([
            'name' => 'custom_' . uniqid(),
            'display_name' => 'Perfil personalizado',
        ]);

        $permissionIds = collect($permissionNames)->map(function (string $name) {
            return Permission::create([
                'name' => $name,
                'display_name' => $name,
                'module' => 'cost_centers',
            ])->id;
        });

        $profile->permissions()->sync($permissionIds);

        return User::factory()->create([
            'role' => 'user',
            'profile_id' => $profile->id,
            'status' => 'active',
        ]);
    }

    private function createCostCenter(): CostCenter
    {
        $company = Company::create([
            'name' => 'Empresa de prueba ' . uniqid(),
            'account' => '0000000001',
        ]);

        return CostCenter::create([
            'code' => 'CC-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Centro visible para perfil personalizado',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
    }
}
