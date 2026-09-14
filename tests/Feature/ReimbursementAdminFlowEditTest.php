<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReimbursementAdminFlowEditTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\DataProvider('editCases')]
    public function test_edits_restore_a_concrete_approval_step(string $mode, string $status, bool $move): void
    {
        Mail::fake();
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $approver = User::factory()->create(['role' => 'director', 'status' => 'active']);
        $company = Company::create(['name' => 'Prueba', 'account' => '0000000001']);
        $source = CostCenter::create(['code' => 'ORIG', 'name' => 'Origen', 'company_id' => $company->id, 'is_active' => true]);
        $target = $move
            ? CostCenter::create(['code' => 'DEST', 'name' => 'Destino', 'company_id' => $company->id, 'is_active' => true])
            : $source;
        $step = ApprovalStep::create(['cost_center_id' => $target->id, 'user_id' => $approver->id, 'order' => 1, 'name' => 'Director']);
        $records = collect(range(1, $mode === 'bulk' ? 2 : 1))->map(fn () => Reimbursement::create([
            'user_id' => $owner->id, 'cost_center_id' => $source->id, 'type' => 'reembolso',
            'status' => $status, 'current_step_id' => null, 'total' => 100, 'moneda' => 'MXN',
            'approved_by_director_id' => $approver->id, 'approved_by_director_at' => now(),
        ]));
        $payload = ['type' => 'reembolso', 'cost_center_id' => $target->id, 'admin_comment' => 'Corregir flujo'];
        if (!$move || $mode === 'single') {
            $payload['status'] = 'enviado';
        }
        $this->actingAs($admin);
        $response = $mode === 'bulk'
            ? $this->post(route('reimbursements.bulk_audit_action'), $payload + ['action' => 'editar', 'ids' => $records->pluck('id')->all()])
            : $this->patch(route('reimbursements.admin_flow_update', $records->first()), $payload);
        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        foreach ($records as $record) {
            $record->refresh();
            $this->assertSame('enviado', $record->status);
            $this->assertEquals($step->id, $record->current_step_id);
            $this->assertEquals($target->id, $record->cost_center_id);
            $this->assertNull($record->approved_by_director_id);
            $this->assertTrue($record->canBeApprovedBy($approver));
            $this->assertSame(1, $record->approvals()->where('action', 'ajuste_flujo')->count());
        }
    }

    public static function editCases(): array
    {
        return [
            'bulk reactivation' => ['bulk', 'rechazado', false],
            'bulk move preserves no stale stage' => ['bulk', 'aprobado_director', true],
            'bulk legacy status move' => ['bulk', 'pendiente_autorizacion', true],
            'single repairs missing step' => ['single', 'enviado', false],
            'single reactivation' => ['single', 'requiere_correccion', false],
        ];
    }
}
