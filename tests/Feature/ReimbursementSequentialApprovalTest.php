<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimbursementSequentialApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_executive_approval_stops_at_the_subdirection_step_assigned_to_another_user(): void
    {
        $executive = User::factory()->create([
            'role' => 'director_ejecutivo',
            'status' => 'active',
        ]);
        $subdirector = User::factory()->create([
            'role' => 'direccion',
            'status' => 'active',
        ]);
        $requester = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);
        $company = Company::create([
            'name' => 'Empresa de prueba ' . uniqid(),
            'account' => '0000000001',
        ]);
        $costCenter = CostCenter::create([
            'code' => 'CC-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Centro de prueba ' . uniqid(),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $executiveStep = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $executive->id,
            'order' => 3,
            'name' => 'Director Ejecutivo N3',
        ]);
        $nextStep = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $subdirector->id,
            'order' => 5,
            'name' => 'Subdirección N5',
        ]);
        $reimbursement = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $requester->id,
            'status' => 'pendiente',
            'current_step_id' => $executiveStep->id,
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($executive)
            ->post(route('reimbursements.bulk_audit_action'), [
                'ids' => [$reimbursement->id],
                'action' => 'aprobado',
            ])
            ->assertRedirect();

        $reimbursement->refresh();

        $this->assertSame('pendiente', $reimbursement->status);
        $this->assertSame($nextStep->id, $reimbursement->current_step_id);
        $this->assertDatabaseHas('reimbursement_approvals', [
            'reimbursement_id' => $reimbursement->id,
            'step_name' => 'Director Ejecutivo N3',
            'action' => 'aprobado',
            'is_bulk' => true,
        ]);
        $this->assertDatabaseMissing('reimbursement_approvals', [
            'reimbursement_id' => $reimbursement->id,
            'step_name' => 'Subdirección N5',
            'action' => 'aprobado',
        ]);

        $this->actingAs($subdirector)
            ->post(route('reimbursements.bulk_audit_action'), [
                'ids' => [$reimbursement->id],
                'action' => 'aprobado',
            ])
            ->assertRedirect();

        $reimbursement->refresh();

        $this->assertSame('pendiente_revision_cxp', $reimbursement->status);
        $this->assertSame($subdirector->id, $reimbursement->approved_by_direccion_id);
        $this->assertNull($reimbursement->approved_by_cxp_id);
        $this->assertDatabaseHas('reimbursement_approvals', [
            'reimbursement_id' => $reimbursement->id,
            'user_id' => $subdirector->id,
            'step_name' => 'Subdirección N5',
            'action' => 'aprobado',
            'is_bulk' => true,
        ]);
    }

    public function test_cxp_cannot_approve_when_subdirection_has_no_audit_entry(): void
    {
        $subdirector = User::factory()->create(['role' => 'direccion', 'status' => 'active']);
        $cxpReviewer = User::factory()->create(['role' => 'accountant', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $company = Company::create([
            'name' => 'Empresa de prueba ' . uniqid(),
            'account' => '0000000002',
        ]);
        $costCenter = CostCenter::create([
            'code' => 'CC-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Centro de prueba ' . uniqid(),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $subdirectionStep = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $subdirector->id,
            'order' => 4,
            'name' => 'Aprobador N4',
        ]);
        $reimbursement = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $requester->id,
            'status' => 'pendiente_revision_cxp',
            'current_step_id' => null,
            'total' => 100,
            'moneda' => 'MXN',
        ]);

        $this->assertSame($subdirectionStep->id, $reimbursement->firstPendingConfiguredApprovalStep()?->id);
        $this->assertFalse($reimbursement->canBeApprovedBy($cxpReviewer));
    }

    public function test_resubmission_returns_to_the_approver_who_requested_the_correction(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $approver = User::factory()->create(['role' => 'director', 'status' => 'active']);
        $company = Company::create([
            'name' => 'Empresa de prueba ' . uniqid(),
            'account' => '0000000003',
        ]);
        $costCenter = CostCenter::create([
            'code' => 'CC-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Centro de prueba ' . uniqid(),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $approvalStep = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $approver->id,
            'order' => 1,
            'name' => 'Control de Obra',
        ]);
        $reimbursement = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $requester->id,
            'status' => 'requiere_correccion',
            'current_step_id' => null,
            'total' => 100,
            'moneda' => 'MXN',
        ]);
        $reimbursement->approvals()->create([
            'user_id' => $approver->id,
            'step_name' => $approvalStep->name,
            'action' => 'requiere_correccion',
            'comment' => 'Corregir beneficiario.',
        ]);

        $this->actingAs($requester)
            ->put(route('reimbursements.update', $reimbursement), [
                'is_resubmission' => '1',
                'user_correction_comment' => 'Beneficiario corregido.',
            ])
            ->assertRedirect();

        $reimbursement->refresh();

        $this->assertSame('pendiente', $reimbursement->status);
        $this->assertSame($approvalStep->id, $reimbursement->current_step_id);
        $this->assertTrue($reimbursement->canBeApprovedBy($approver));
    }
}
