<?php

namespace Tests\Feature;

use App\Models\User;
use Mockery;
use Tests\TestCase;

class ReimbursementTabVisibilityTest extends TestCase
{
    public function test_profile_permission_controls_reimbursement_tab_visibility(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('canPerform')
            ->with('reimbursements.view_own')
            ->andReturnTrue();
        $user->shouldReceive('canPerform')
            ->with('reimbursements.view_management')
            ->andReturnFalse();
        $user->shouldReceive('canPerform')
            ->with('reimbursements.view_own_history')
            ->andReturnFalse();

        $this->assertTrue($user->canViewReimbursementTab('active'));
        $this->assertFalse($user->canViewReimbursementTab('management'));
        $this->assertFalse($user->canViewReimbursementTab('history'));
    }

    public function test_legacy_weekly_summary_uses_management_permission(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('canPerform')
            ->once()
            ->with('reimbursements.view_management')
            ->andReturnTrue();

        $this->assertTrue($user->canViewReimbursementTab('weekly_summary'));
        $this->assertFalse($user->canViewReimbursementTab('unknown'));
    }
}
