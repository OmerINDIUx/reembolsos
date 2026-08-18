<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimbursementDraftDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_own_draft_redirects_to_the_draft_list_instead_of_the_deleted_resource(): void
    {
        $user = User::factory()->create([
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
        $deletedId = $draft->id;

        $this->actingAs($user)
            ->from(route('reimbursements.edit', $draft))
            ->delete(route('reimbursements.destroy', $draft))
            ->assertRedirect(route('reimbursements.create'))
            ->assertSessionHas('success', 'Borrador eliminado correctamente.');

        $this->assertDatabaseMissing('reimbursements', ['id' => $deletedId]);
    }
}
