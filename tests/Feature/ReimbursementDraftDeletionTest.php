<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimbursementDraftDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_own_draft_redirects_to_the_draft_list_instead_of_the_deleted_resource(): void
    {
        $user = $this->userWithDeletePermission();
        $user->update([
            'role' => 'user',
            'status' => 'active',
        ]);
        $draft = Reimbursement::create([
            'user_id' => $user->id,
            'created_by_id' => $user->id,
            'status' => 'borrador',
            'type' => 'reembolso',
            'title' => 'Borrador temporal',
            'total' => 100,
            'moneda' => 'MXN',
        ]);
        $this->actingAs($user)
            ->from(route('reimbursements.edit', $draft))
            ->delete(route('reimbursements.destroy', $draft))
            ->assertRedirect(route('reimbursements.create'))
            ->assertSessionHas('success', 'Borrador enviado a borrados. Podrá recuperarse desde Auditoría de dispositivos.');

        $this->assertDatabaseHas('reimbursements', [
            'id' => $draft->id,
            'status' => 'eliminado',
            'deleted_by_id' => $user->id,
        ]);
    }

    public function test_deleting_a_draft_does_not_archive_a_processed_child(): void
    {
        $user = $this->userWithDeletePermission();
        $draft = Reimbursement::create([
            'user_id' => $user->id,
            'created_by_id' => $user->id,
            'status' => 'borrador',
            'type' => 'viaje',
            'title' => 'Borrador padre',
            'total' => 0,
            'moneda' => 'MXN',
        ]);
        $paid = Reimbursement::create([
            'user_id' => $user->id,
            'created_by_id' => $user->id,
            'parent_id' => $draft->id,
            'status' => 'pagado',
            'type' => 'reembolso',
            'title' => 'Hijo pagado',
            'total' => 500,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($user)->delete(route('reimbursements.destroy', $draft))->assertRedirect();

        $this->assertDatabaseHas('reimbursements', [
            'id' => $paid->id,
            'status' => 'pagado',
            'deleted_at' => null,
        ]);
    }

    public function test_user_without_delete_permission_cannot_delete_an_own_draft(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $draft = Reimbursement::create([
            'user_id' => $user->id,
            'created_by_id' => $user->id,
            'status' => 'borrador',
            'type' => 'reembolso',
            'title' => 'Borrador protegido',
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($user)
            ->delete(route('reimbursements.destroy', $draft))
            ->assertForbidden();

        $this->assertDatabaseHas('reimbursements', [
            'id' => $draft->id,
            'status' => 'borrador',
            'deleted_at' => null,
        ]);
    }

    private function userWithDeletePermission(): User
    {
        $permission = Permission::create([
            'name' => 'reimbursements.delete',
            'display_name' => 'Eliminar reembolsos',
            'module' => 'reimbursements',
        ]);
        $profile = Profile::create(['name' => 'reimbursement_deleter']);
        $profile->permissions()->attach($permission);

        return User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'profile_id' => $profile->id,
        ]);
    }
}
