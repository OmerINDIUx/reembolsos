<?php

namespace Tests\Feature;

use App\Models\Reimbursement;
use App\Models\ReimbursementApproval;
use App\Models\User;
use Illuminate\Support\Collection;
use LogicException;
use Tests\TestCase;

class ImmutableApprovalHistoryTest extends TestCase
{
    public function test_completed_step_keeps_the_user_recorded_in_the_audit_log(): void
    {
        $originalApprover = new User(['name' => 'Aprobador Original']);
        $originalApprover->id = 10;
        $newApprover = new User(['name' => 'Aprobador Nuevo']);
        $newApprover->id = 20;
        $approval = new ReimbursementApproval([
            'user_id' => $originalApprover->id,
            'step_name' => 'Dirección',
            'action' => 'aprobado',
        ]);
        $approval->setRelation('user', $originalApprover);

        // The current assignment may change, but the completed step must use its audit actor.
        $currentAssignedApprover = $newApprover;
        $reimbursement = new Reimbursement();
        $reimbursement->setRelation('approvals', new Collection([$approval]));
        $historicalApproval = $reimbursement->approvedLogForStep('Dirección');

        $this->assertSame($originalApprover->id, $historicalApproval?->user_id);
        $this->assertSame('Aprobador Original', $historicalApproval?->user?->name);
        $this->assertNotSame($currentAssignedApprover->id, $historicalApproval?->user_id);
    }

    public function test_existing_approval_history_cannot_be_edited(): void
    {
        $approval = $this->approvalRecord();
        $approval->user_id = 99;

        $this->expectException(LogicException::class);

        $approval->save();
    }

    public function test_existing_approval_history_cannot_be_deleted(): void
    {
        $approval = $this->approvalRecord();

        $this->expectException(LogicException::class);

        $approval->delete();
    }

    private function approvalRecord(): ReimbursementApproval
    {
        $approval = new ReimbursementApproval([
            'user_id' => 10,
            'step_name' => 'Dirección',
            'action' => 'aprobado',
        ]);
        $approval->id = 1;
        $approval->exists = true;
        $approval->syncOriginal();

        return $approval;
    }
}
