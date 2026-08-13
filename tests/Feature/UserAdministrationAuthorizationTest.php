<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserAdministrationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Profile $adminProfile;
    private Profile $userProfile;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminProfile = Profile::create([
            'name' => 'admin',
            'display_name' => 'Administrador (Full)',
        ]);

        Profile::create([
            'name' => 'admin_view',
            'display_name' => 'Administrador (Lectura)',
        ]);

        $this->userProfile = Profile::create([
            'name' => 'user',
            'display_name' => 'Usuario General',
        ]);

        $managerProfile = Profile::create([
            'name' => 'user_manager',
            'display_name' => 'Gestor de usuarios',
        ]);

        $permissionIds = collect(['users.view', 'users.create', 'users.edit', 'users.delete'])
            ->map(fn (string $name) => Permission::create([
                'name' => $name,
                'display_name' => $name,
                'module' => 'users',
            ])->id);

        $managerProfile->permissions()->sync($permissionIds);

        $this->manager = User::factory()->create([
            'role' => 'user',
            'profile_id' => $managerProfile->id,
        ]);
    }

    public function test_user_manager_can_create_a_regular_user_but_cannot_select_an_admin_profile(): void
    {
        Mail::fake();

        $this->actingAs($this->manager)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee($this->userProfile->display_name)
            ->assertDontSee($this->adminProfile->display_name);

        $this->actingAs($this->manager)
            ->post(route('users.store'), [
                'name' => 'Usuario Operativo',
                'email' => 'operativo@grupoindi.com',
                'profile_id' => $this->userProfile->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email_normalized' => 'operativo@grupoindi.com',
            'profile_id' => $this->userProfile->id,
            'role' => 'user',
        ]);
    }

    public function test_user_manager_cannot_create_a_user_with_an_external_email_domain(): void
    {
        Mail::fake();

        $this->actingAs($this->manager)
            ->post(route('users.store'), [
                'name' => 'Usuario Externo',
                'email' => 'externo@gmail.com',
                'profile_id' => $this->userProfile->id,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', [
            'email_normalized' => 'externo@gmail.com',
        ]);
    }

    public function test_user_manager_cannot_create_an_administrator_with_a_direct_request(): void
    {
        Mail::fake();

        $this->actingAs($this->manager)
            ->post(route('users.store'), [
                'name' => 'Administrador no autorizado',
                'email' => 'admin-no-autorizado@construlerma.com',
                'profile_id' => $this->adminProfile->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', [
            'email_normalized' => 'admin-no-autorizado@construlerma.com',
        ]);
    }

    public function test_user_manager_can_edit_a_regular_user(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'usuario@archandel.com',
            'profile_id' => $this->userProfile->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->manager)
            ->get(route('users.edit', $user))
            ->assertOk();

        $this->actingAs($this->manager)
            ->put(route('users.update', $user), [
                'name' => 'Usuario Actualizado',
                'email' => $user->email,
                'profile_id' => $this->userProfile->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame('Usuario Actualizado', $user->refresh()->name);
    }

    public function test_user_manager_cannot_edit_or_update_an_administrator(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@grupoindi.com',
            'role' => 'admin',
            'profile_id' => $this->adminProfile->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->manager)
            ->get(route('users.edit', $admin))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->put(route('users.update', $admin), [
                'name' => 'Administrador alterado',
                'email' => $admin->email,
                'profile_id' => $this->userProfile->id,
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertNotSame('Administrador alterado', $admin->refresh()->name);
        $this->assertSame('admin', $admin->role);
    }

    public function test_admin_can_create_another_administrator(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'profile_id' => $this->adminProfile->id,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Nuevo Administrador',
                'email' => 'nuevo-admin@archandel.com',
                'profile_id' => $this->adminProfile->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email_normalized' => 'nuevo-admin@archandel.com',
            'profile_id' => $this->adminProfile->id,
            'role' => 'admin',
        ]);
    }
}
