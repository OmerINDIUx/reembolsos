<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use App\Models\ApprovalStep;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ReimbursementAdminFlowPreservationTest extends TestCase
{
    public function test_an_edit_keeps_the_current_stage_and_approval_fields(): void
    {
        $this->assertPreserved(false);
    }

    public function test_a_missing_assignment_resumes_the_pending_stage_without_erasing_approvals(): void
    {
        $this->assertPreserved(true);
    }

    private function assertPreserved(bool $missing): void
    {
        $center = Mockery::mock(CostCenter::class)->makePartial();
        $center->id = 10;
        $center->shouldReceive('load')->once()->andReturnSelf();
        $step = new ApprovalStep(['order' => 3, 'name' => 'Director Ejecutivo']);
        $step->id = 30;
        $record = Mockery::mock(Reimbursement::class)->makePartial();
        $record->fill(['status' => 'aprobado_control', 'type' => 'reembolso', 'cost_center_id' => 10,
            'current_step_id' => $missing ? null : 30, 'approved_by_control_id' => 20]);
        $record->setRelation('costCenter', $center);
        $record->setRelation('currentStep', $missing ? null : $step);
        if ($missing) {
            $record->shouldReceive('firstPendingConfiguredApprovalStep')->once()->andReturn($step);
        }
        [$data, , $error] = (new ReflectionMethod(ReimbursementController::class, 'prepareAdminFlowAdjustment'))
            ->invoke(new ReimbursementController(), $record, new User(['name' => 'Administrador']), [
                'status' => 'enviado', 'admin_comment' => 'Conservar avance',
            ]);
        $this->assertNull($error);
        $this->assertSame('aprobado_control', $data['status']);
        $this->assertSame(30, $data['current_step_id']);
        foreach (array_keys($data) as $key) {
            $this->assertFalse(str_starts_with($key, 'approved_by_'), 'La edición no debe sobrescribir aprobaciones.');
        }
    }
}
