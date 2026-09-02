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
        $owner = User::factory()->create(['name' => 'Persona registradora', 'role' => 'user', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $uuid = '12345678-1234-1234-1234-123456789012';

        $existing = Reimbursement::create([
            'user_id' => $owner->id,
            'created_by_id' => $owner->id,
            'status' => 'pendiente_autorizacion',
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
            ->assertJsonPath('errors.0.folio', $existing->fresh()->folio)
            ->assertJsonPath('errors.0.registered_by', 'Persona registradora')
            ->assertJsonPath('errors.0.status_label', 'pendiente de autorización')
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

    public function test_auto_save_cannot_use_a_processed_reimbursement_as_a_draft(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $paid = Reimbursement::create([
            'user_id' => $requester->id,
            'created_by_id' => $requester->id,
            'status' => 'pagado',
            'type' => 'reembolso',
            'title' => 'Reembolso pagado',
            'total' => 500,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($requester)
            ->postJson(route('reimbursements.auto_save'), [
                'type' => 'reembolso',
                'has_invoice' => '1',
                'items' => [[
                    'draft_id' => $paid->id,
                    'total' => 1,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('reimbursements', [
            'id' => $paid->id,
            'status' => 'pagado',
            'total' => 500,
            'parent_id' => null,
        ]);
    }

    public function test_xml_upload_reports_the_existing_internal_folio_owner_and_stage_before_auto_save(): void
    {
        $owner = User::factory()->create(['name' => 'Ana Registradora', 'role' => 'user', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $uuid = '11111111-2222-3333-4444-555555555555';

        Reimbursement::create([
            'user_id' => $owner->id,
            'created_by_id' => $owner->id,
            'folio' => 'REE-000321',
            'status' => 'pendiente_revision_cxp',
            'type' => 'reembolso',
            'uuid' => $uuid,
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Fecha="2026-09-02T10:00:00" SubTotal="100.00" Total="116.00" Moneda="MXN" TipoDeComprobante="I" LugarExpedicion="64000">
  <cfdi:Emisor Rfc="AAA010101AAA" Nombre="EMISOR" RegimenFiscal="601"/>
  <cfdi:Receptor Rfc="BBB010101BBB" Nombre="RECEPTOR" UsoCFDI="G03"/>
  <cfdi:Complemento><tfd:TimbreFiscalDigital UUID="{$uuid}"/></cfdi:Complemento>
</cfdi:Comprobante>
XML;

        $this->actingAs($requester)
            ->post(route('reimbursements.parse'), [
                'xml_file' => UploadedFile::fake()->createWithContent('duplicado.xml', $xml),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'duplicate_cfdi')
            ->assertJsonPath('folio', 'REE-000321')
            ->assertJsonPath('registered_by', 'Ana Registradora')
            ->assertJsonPath('status_label', 'pendiente de revisión por Cuentas por Pagar');
    }
}
