<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReimbursementAutoSaveErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_save_explains_which_cfdi_is_duplicated_and_what_the_user_should_do(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $uuid = '12345678-1234-1234-1234-123456789012';

        $existing = Reimbursement::create([
            'user_id' => $owner->id,
            'created_by_id' => $owner->id,
            'status' => 'pendiente',
            'type' => 'reembolso',
            'title' => 'Reembolso existente',
            'uuid' => $uuid,
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $response = $this->actingAs($requester)->postJson(route('reimbursements.auto_save'), [
            'type' => 'reembolso',
            'has_invoice' => '1',
            'items' => [[
                'uuid' => $uuid,
                'total' => 100,
                'moneda' => 'MXN',
            ]],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.0.type', 'duplicate_cfdi')
            ->assertJsonPath('errors.0.reference', $response->json('errors.0.diagnostic_id'))
            ->assertJsonFragment([
                'message' => "El gasto #1 no se guardó porque el CFDI con UUID {$uuid} ya está registrado en el reembolso {$existing->fresh()->folio} con estado pendiente de autorización.",
                'action' => 'Retira este comprobante de la solicitud o verifica el reembolso existente antes de continuar.',
            ]);
    }

    public function test_auto_save_reuses_a_draft_created_by_the_same_requester(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $uuid = '87654321-4321-4321-4321-210987654321';

        $draft = Reimbursement::create([
            'user_id' => $requester->id,
            'created_by_id' => $requester->id,
            'status' => 'borrador',
            'type' => 'reembolso',
            'title' => 'Borrador existente',
            'uuid' => $uuid,
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($requester)->postJson(route('reimbursements.auto_save'), [
            'type' => 'reembolso',
            'has_invoice' => '1',
            'items' => [[
                'uuid' => $uuid,
                'total' => 125,
                'moneda' => 'MXN',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('ids.0.id', $draft->id);

        $this->assertDatabaseHas('reimbursements', [
            'id' => $draft->id,
            'uuid' => $uuid,
            'total' => 125,
        ]);
        $this->assertSame(1, Reimbursement::where('uuid', $uuid)->count());
    }

    public function test_failed_auto_save_removes_only_the_new_unpersisted_file(): void
    {
        Storage::fake();

        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $existingPath = 'reimbursements/xmls/drafts/existing.xml';
        Storage::put($existingPath, '<existing/>');

        $draft = Reimbursement::create([
            'user_id' => $requester->id,
            'created_by_id' => $requester->id,
            'status' => 'borrador',
            'type' => 'reembolso',
            'xml_path' => $existingPath,
        ]);

        $this->actingAs($requester)->post(route('reimbursements.auto_save'), [
            'type' => 'reembolso',
            'cost_center_id' => 999999,
            'has_invoice' => '1',
            'items' => [[
                'draft_id' => $draft->id,
                'xml_file' => UploadedFile::fake()->createWithContent('replacement.xml', '<replacement/>'),
            ]],
        ])->assertUnprocessable();

        Storage::assertExists($existingPath);
        $this->assertSame([$existingPath], Storage::allFiles('reimbursements/xmls/drafts'));
        $this->assertSame($existingPath, $draft->fresh()->xml_path);
    }
}
