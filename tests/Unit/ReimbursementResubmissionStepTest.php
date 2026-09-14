<?php

namespace Tests\Unit;

use App\Http\Controllers\ReimbursementController;
use App\Models\ApprovalStep;
use App\Models\Reimbursement;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ReimbursementResubmissionStepTest extends TestCase
{
    public function test_a_correction_without_a_return_event_resumes_the_pending_step(): void
    {
        $record = Mockery::mock(Reimbursement::class)->makePartial();
        $record->setRelation('currentStep', null);
        $step = new ApprovalStep(['order' => 3]);
        $step->id = 30;
        $record->shouldReceive('firstPendingConfiguredApprovalStep')->once()->andReturn($step);
        $this->assertSame($step, $this->resolve($record));
    }

    public function test_a_correction_without_a_return_event_keeps_its_assigned_step(): void
    {
        $record = Mockery::mock(Reimbursement::class)->makePartial();
        $record->cost_center_id = 15;
        $step = new ApprovalStep(['cost_center_id' => 15, 'order' => 3]);
        $record->setRelation('currentStep', $step);
        $record->shouldNotReceive('firstPendingConfiguredApprovalStep');
        $this->assertSame($step, $this->resolve($record));
    }

    public function test_a_completed_flow_without_a_return_event_has_no_remaining_step(): void
    {
        $record = Mockery::mock(Reimbursement::class)->makePartial();
        $record->setRelation('currentStep', null);
        $record->shouldReceive('firstPendingConfiguredApprovalStep')->once()->andReturnNull();
        $this->assertNull($this->resolve($record));
    }

    private function resolve(Reimbursement $record): ?ApprovalStep
    {
        return (new ReflectionMethod(ReimbursementController::class, 'resubmissionApprovalStep'))
            ->invoke(new ReimbursementController(), $record, null);
    }
}
