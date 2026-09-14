<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReimbursementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $director;
    private User $control;
    private User $reviewer;
    private User $payer;
    private CostCenter $center;
    private ApprovalStep $first;
    private ApprovalStep $second;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Storage::fake();
        foreach (['owner' => 'user', 'director' => 'director', 'control' => 'control_obra', 'reviewer' => 'accountant', 'payer' => 'tesoreria'] as $property => $role) {
            $this->$property = User::factory()->create(['role' => $role, 'status' => 'active']);
        }
        $company = Company::create(['name' => 'Empresa', 'account' => '0000000001']);
        $this->center = CostCenter::create(['code' => 'LIFE', 'name' => 'Ciclo completo', 'company_id' => $company->id, 'is_active' => true]);
        $this->center->authorizedUsers()->attach($this->owner->id, ['can_do_special' => false]);
        $this->first = ApprovalStep::create(['cost_center_id' => $this->center->id, 'user_id' => $this->director->id, 'order' => 1, 'name' => 'Director N1']);
        $this->second = ApprovalStep::create(['cost_center_id' => $this->center->id, 'user_id' => $this->control->id, 'order' => 2, 'name' => 'Control de Obra']);
    }

    public function test_capture_publish_correct_approve_and_return_from_payment(): void
    {
        $payload = $this->manualPayload();
        $response = $this->actingAs($this->owner)->postJson(route('reimbursements.auto_save'), $payload);
        $response->assertOk()->assertJsonPath('success', true);
        $record = Reimbursement::findOrFail($response->json('main_id'));
        $this->assertSame('borrador', $record->status);
        $payload['items'][0]['draft_id'] = $record->id;
        unset($payload['items'][0]['pdf_file'], $payload['items'][0]['ticket_file']);
        $this->post(route('reimbursements.bulk_store'), $payload)->assertSessionHasNoErrors()->assertSessionHas('success');
        $record->refresh();
        $this->assertSame('enviado', $record->status);
        $this->assertEquals($this->first->id, $record->current_step_id);
        $this->assertFalse($record->canBeApprovedBy($this->payer));
        $this->approve($record, $this->director);
        $this->assertEquals($this->second->id, $record->current_step_id);
        $this->actingAs($this->control)->put(route('reimbursements.update', $record), [
            'status' => 'requiere_correccion', 'rejection_reason' => 'Aclarar comprobante',
        ])->assertSessionHas('success');
        $this->actingAs($this->owner)->put(route('reimbursements.update', $record), [
            'is_resubmission' => '1', 'user_correction_comment' => 'Comprobante aclarado',
        ])->assertSessionHas('success');
        $record->refresh();
        $this->assertEquals($this->second->id, $record->current_step_id);
        $this->assertEquals($this->director->id, $record->approved_by_director_id);
        $this->assertSame(1, $record->approvals()->where('approval_step_id', $this->first->id)->where('action', 'aprobado')->count());
        $this->approve($record, $this->control);
        $this->assertSame('pendiente_revision_cxp', $record->status);
        $this->assertFalse($record->canBeApprovedBy($this->payer));
        $this->approve($record, $this->reviewer);
        $this->assertSame('pendiente_pago', $record->status);
        $this->assertNull($record->approved_by_treasury_at);
        $this->actingAs($this->payer)->get(route('reimbursements.payment_file', ['ids' => $record->id]))->assertNotFound();
        $this->approve($record, $this->payer);
        $this->assertNotNull($record->approved_by_treasury_at);
        $this->assertNotNull($record->payment_week);
        $this->assertFalse($record->canBeApprovedBy($this->payer));
        $export = $this->get(route('reimbursements.payment_file', ['ids' => $record->id]));
        $export->assertOk()->assertDownload();
        $this->assertStringContainsString('116.00', $export->streamedContent());
        $this->actingAs($this->payer)->post(route('reimbursements.payment_return'), [
            'ids' => [$record->id], 'comment' => 'Revisar datos del pago',
        ])->assertSessionHas('success');
        $record->refresh();
        $this->assertNull($record->approved_by_treasury_at);
        $this->assertNotNull($record->approved_by_cxp_at);
        $this->assertTrue($record->canBeApprovedBy($this->payer));
        $this->get(route('reimbursements.payment_file', ['ids' => $record->id]))->assertNotFound();
    }

    public function test_completed_or_returned_items_cannot_be_approved_even_with_a_stale_step(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        foreach (['borrador', 'requiere_correccion', 'rechazado', 'pagado', 'aprobado', 'en_evento'] as $status) {
            $record = $this->record($status);
            $this->assertFalse($record->canBeApprovedBy($this->director), $status);
            $this->assertFalse($record->canBeApprovedBy($admin), $status . ' admin');
        }
    }

    public function test_publishing_a_draft_id_cannot_overwrite_an_active_reimbursement(): void
    {
        $record = $this->record('aprobado_director');
        $payload = $this->manualPayload();
        $payload['items'][0]['draft_id'] = $record->id;
        $this->actingAs($this->owner)->post(route('reimbursements.bulk_store'), $payload)->assertRedirect();
        $this->assertSame('aprobado_director', $record->fresh()->status);
        $this->assertSame(1, Reimbursement::count());
    }

    public function test_duplicate_bulk_ids_do_not_approve_two_levels(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $record = $this->record('enviado');
        $this->actingAs($admin)->post(route('reimbursements.bulk_audit_action'), [
            'ids' => [$record->id, $record->id], 'action' => 'aprobado',
        ])->assertSessionHasErrors();
        $this->assertEquals($this->first->id, $record->fresh()->current_step_id);
        $this->assertSame(0, $record->approvals()->count());
    }

    public function test_csv_cannot_bypass_outstanding_configured_approvals(): void
    {
        $record = $this->record('pendiente_revision_cxp');
        $csv = UploadedFile::fake()->createWithContent('aprobaciones.csv', "Folio,UUID,Total\n{$record->folio},N/A,116\n");
        $this->actingAs($this->reviewer)->post(route('reimbursements.bulk_approve'), ['csv_file' => $csv])->assertRedirect();
        $this->assertSame('pendiente_revision_cxp', $record->fresh()->status);
        $this->assertNull($record->fresh()->approved_by_cxp_at);
    }

    public function test_a_cost_center_edit_preserves_completed_levels_after_the_next_approval(): void
    {
        $record = $this->record('enviado');
        $this->approve($record, $this->director);
        $target = CostCenter::create(['code' => 'NEW', 'name' => 'Nuevo centro', 'company_id' => $this->center->company_id, 'is_active' => true]);
        foreach ([$this->first, $this->second] as $sourceStep) {
            ApprovalStep::create(['cost_center_id' => $target->id, 'user_id' => $sourceStep->user_id, 'order' => $sourceStep->order, 'name' => $sourceStep->name]);
        }
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->patch(route('reimbursements.admin_flow_update', $record), [
            'status' => '', 'type' => 'reembolso', 'cost_center_id' => $target->id, 'admin_comment' => 'Centro correcto',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $record->refresh();
        $this->approve($record, $this->control);
        $this->assertSame('pendiente_revision_cxp', $record->status);
        $this->assertTrue($record->configuredApprovalFlowIsComplete());
        $this->assertTrue(Reimbursement::whereKey($record->id)->withCompletedConfiguredApprovalFlow()->exists());
        $this->assertSame(2, $record->approvals()->where('action', 'aprobado')->count());
    }

    public function test_bulk_return_from_payers_resumes_at_payers(): void
    {
        $record = $this->record('enviado');
        $this->approve($record, $this->director);
        $this->approve($record, $this->control);
        $this->approve($record, $this->reviewer);
        $this->actingAs($this->payer)->post(route('reimbursements.bulk_audit_action'), [
            'ids' => [$record->id], 'action' => 'requiere_correccion', 'rejection_reason' => 'Corregir comprobante',
        ])->assertSessionHas('success');
        $this->actingAs($this->owner)->put(route('reimbursements.update', $record), [
            'is_resubmission' => '1', 'user_correction_comment' => 'Comprobante corregido',
        ])->assertSessionHas('success');
        $record->refresh();
        $this->assertSame('pendiente_pago', $record->status);
        $this->assertNotNull($record->approved_by_cxp_at);
        $this->assertNull($record->current_step_id);
        $this->assertTrue($record->canBeApprovedBy($this->payer));
    }

    public function test_an_invalid_center_change_does_not_delete_the_existing_attachment(): void
    {
        $record = $this->record('requiere_correccion');
        Storage::put('original.pdf', 'original');
        $record->update(['pdf_path' => 'original.pdf']);
        $target = CostCenter::create(['code' => 'EMPTY', 'name' => 'Sin etapa equivalente', 'company_id' => $this->center->company_id, 'is_active' => true]);
        $target->authorizedUsers()->attach($this->owner->id);
        $this->actingAs($this->owner)->put(route('reimbursements.update', $record), [
            'is_resubmission' => '1', 'user_correction_comment' => 'Cambiar centro', 'cost_center_id' => $target->id,
            'pdf_file' => UploadedFile::fake()->create('nuevo.pdf', 1, 'application/pdf'),
        ])->assertSessionHas('error');
        Storage::assertExists('original.pdf');
        $this->assertSame('original.pdf', $record->fresh()->pdf_path);
        $this->assertEquals($this->center->id, $record->fresh()->cost_center_id);
    }

    public function test_an_audit_failure_rolls_back_the_approval(): void
    {
        $record = $this->record('enviado');
        $event = 'eloquent.creating: App\\Models\\ReimbursementApproval';
        \Illuminate\Support\Facades\Event::listen($event, fn () => throw new \RuntimeException('Fallo de auditoría simulado'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->director)->put(route('reimbursements.update', $record), ['status' => 'aprobado']);
            $this->fail('El fallo simulado debe interrumpir el guardado.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fallo de auditoría simulado', $exception->getMessage());
        } finally {
            \Illuminate\Support\Facades\Event::forget($event);
        }
        $this->assertSame('enviado', $record->fresh()->status);
        $this->assertEquals($this->first->id, $record->fresh()->current_step_id);
        $this->assertNull($record->fresh()->approved_by_director_at);
        $this->assertSame(0, $record->approvals()->count());
    }

    public function test_read_only_users_cannot_capture_drafts_or_approve(): void
    {
        $viewer = User::factory()->create(['role' => 'admin_view', 'status' => 'active']);
        $this->actingAs($viewer)->postJson(route('reimbursements.auto_save'), $this->manualPayload())->assertForbidden();
        $record = $this->record('enviado');
        $this->first->update(['user_id' => $viewer->id]);
        $this->assertFalse($record->fresh()->canBeApprovedBy($viewer));
    }

    public function test_an_item_cannot_use_the_workflow_of_a_different_center(): void
    {
        $payload = $this->manualPayload();
        $payload['items'][0]['cost_center_id'] = $this->center->id + 100;
        $this->actingAs($this->owner)->post(route('reimbursements.bulk_store'), $payload)->assertSessionHas('warning');
        $this->assertSame(0, Reimbursement::count());
    }

    public function test_invalid_item_shape_returns_validation_errors_instead_of_a_server_error(): void
    {
        $this->actingAs($this->owner)->postJson(route('reimbursements.bulk_store'), ['items' => 'invalid'])->assertUnprocessable();
    }

    public function test_repeated_csv_rows_do_not_approve_review_and_payment_together(): void
    {
        $record = $this->record('enviado');
        $this->approve($record, $this->director);
        $this->approve($record, $this->control);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $row = "{$record->folio},N/A,116\n";
        $csv = UploadedFile::fake()->createWithContent('repetido.csv', "Folio,UUID,Total\n" . $row . $row);
        $this->actingAs($admin)->post(route('reimbursements.bulk_approve'), ['csv_file' => $csv])->assertRedirect();
        $record->refresh();
        $this->assertSame('pendiente_pago', $record->status);
        $this->assertNotNull($record->approved_by_cxp_at);
        $this->assertNull($record->approved_by_treasury_at);
    }

    public function test_empty_csv_returns_a_clear_error(): void
    {
        $csv = UploadedFile::fake()->createWithContent('vacio.csv', '');
        $this->actingAs($this->reviewer)->post(route('reimbursements.bulk_approve'), ['csv_file' => $csv])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_a_center_with_an_extra_earlier_level_cannot_restart_the_request(): void
    {
        $record = $this->record('enviado');
        $this->approve($record, $this->director);
        $target = CostCenter::create(['code' => 'DIFF', 'name' => 'Flujo diferente', 'company_id' => $this->center->company_id, 'is_active' => true]);
        ApprovalStep::create(['cost_center_id' => $target->id, 'user_id' => $this->director->id, 'order' => 1, 'name' => 'Otro nivel previo']);
        ApprovalStep::create(['cost_center_id' => $target->id, 'user_id' => $this->control->id, 'order' => 2, 'name' => $this->second->name]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->patch(route('reimbursements.admin_flow_update', $record), [
            'status' => '', 'type' => 'reembolso', 'cost_center_id' => $target->id, 'admin_comment' => 'Cambiar centro',
        ])->assertSessionHasErrors('flow', null, 'adminFlow');
        $this->assertEquals($this->center->id, $record->fresh()->cost_center_id);
        $this->assertEquals($this->second->id, $record->fresh()->current_step_id);
    }

    private function record(string $status): Reimbursement
    {
        return Reimbursement::create(['user_id' => $this->owner->id, 'cost_center_id' => $this->center->id,
            'type' => 'reembolso', 'status' => $status, 'current_step_id' => $this->first->id,
            'total' => 116, 'subtotal' => 100, 'moneda' => 'MXN']);
    }

    private function approve(Reimbursement $record, User $user): void
    {
        $this->actingAs($user)->put(route('reimbursements.update', $record), ['status' => 'aprobado'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $record->refresh();
    }

    private function manualPayload(): array
    {
        return ['type' => 'reembolso', 'has_invoice' => '0', 'cost_center_id' => $this->center->id,
            'week' => now()->format('W-Y'), 'items' => [[
                'category' => 'materiales diversos', 'observaciones' => 'Compra de material',
                'nombre_emisor' => 'Proveedor', 'fecha' => now()->toDateString(), 'subtotal' => 100, 'total' => 116,
                'pdf_file' => UploadedFile::fake()->create('comprobante.pdf', 1, 'application/pdf'),
                'ticket_file' => UploadedFile::fake()->create('ticket.pdf', 1, 'application/pdf'),
            ]]];
    }
}
