<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateApprovalStepHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_approval_only_completes_its_exact_configured_step_when_names_are_duplicated(): void
    {
        $approver = User::factory()->create(['status' => 'active']);
        $requester = User::factory()->create(['status' => 'active']);
        $company = Company::create(['name' => 'Empresa ' . uniqid(), 'account' => '0000000099']);
        $costCenter = CostCenter::create([
            'code' => 'CC-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Centro ' . uniqid(),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $firstStep = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $approver->id,
            'order' => 1,
            'name' => 'Aprobador N4',
        ]);
        $secondStep = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $approver->id,
            'order' => 2,
            'name' => 'Aprobador N4',
        ]);
        $reimbursement = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $requester->id,
            'status' => 'enviado',
            'current_step_id' => $secondStep->id,
            'total' => 100,
            'moneda' => 'MXN',
        ]);
        $reimbursement->approvals()->create([
            'approval_step_id' => $firstStep->id,
            'user_id' => $approver->id,
            'step_name' => 'Aprobador N4',
            'action' => 'aprobado',
            'comment' => 'Aprobación Manual',
        ]);

        $logs = $reimbursement->approvedLogsForConfiguredSteps();

        $this->assertTrue($logs->has($firstStep->id));
        $this->assertFalse($logs->has($secondStep->id));
        $this->assertSame($secondStep->id, $reimbursement->firstPendingConfiguredApprovalStep()?->id);
    }
}
