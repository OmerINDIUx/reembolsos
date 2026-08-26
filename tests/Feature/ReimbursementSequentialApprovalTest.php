<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReimbursementSequentialApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_approval_stops_at_the_subdirection_step_assigned_to_another_user(): void
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

        // A historical action by the subdirector must never complete their
        // pending Subdirección step automatically.
        $reimbursement->approvals()->create([
            'user_id' => $subdirector->id,
            'step_name' => 'Participación anterior',
            'action' => 'aprobado',
            'comment' => 'Aprobación registrada en un nivel anterior.',
        ]);

        $this->actingAs($executive)
            ->patch(route('reimbursements.update', $reimbursement), [
                'status' => 'aprobado',
                'approval_token' => (string) Str::uuid(),
            ])
            ->assertRedirect();

        $reimbursement->refresh();

        $this->assertSame('pendiente', $reimbursement->status);
        $this->assertSame($nextStep->id, $reimbursement->current_step_id);
        $this->assertDatabaseHas('reimbursement_approvals', [
            'reimbursement_id' => $reimbursement->id,
            'step_name' => 'Director Ejecutivo N3',
            'action' => 'aprobado',
        ]);
        $this->assertDatabaseMissing('reimbursement_approvals', [
            'reimbursement_id' => $reimbursement->id,
            'step_name' => 'Subdirección N5',
            'action' => 'aprobado',
        ]);
    }
}
