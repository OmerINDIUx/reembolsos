<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CostCenterDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_centers_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $costCenter = $this->createCostCenter();

        $this->assertFalse(Route::has('cost_centers.destroy'));

        $this->actingAs($admin)
            ->delete('/cost_centers/' . $costCenter->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('cost_centers', ['id' => $costCenter->id]);
    }

    public function test_deactivation_applies_an_individual_decision_to_each_open_reimbursement(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $costCenter = $this->createCostCenter();
        $step = ApprovalStep::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $admin->id,
            'order' => 1,
            'name' => 'Dirección',
        ]);

        $continued = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $requester->id,
            'status' => 'pendiente',
            'current_step_id' => $step->id,
            'total' => 100,
            'moneda' => 'MXN',
        ]);
        $rejected = Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'user_id' => $requester->id,
            'status' => 'requiere_correccion',
            'current_step_id' => $step->id,
            'total' => 200,
            'moneda' => 'MXN',
        ]);

        $this->actingAs($admin)
            ->get(route('cost_centers.deactivation', $costCenter))
            ->assertOk()
            ->assertSee('Continuar hasta concluir')
            ->assertSee('Detener y rechazar')
            ->assertSee('Seleccionar resultados filtrados')
            ->assertSee('Aplicar a seleccionados')
            ->assertSee('Todos los estados')
            ->assertDontSee('Eliminar centro');

        $this->actingAs($admin)
            ->patch(route('cost_centers.toggle_status', $costCenter), [
                'reimbursement_decisions' => [
                    $continued->id => 'continue',
                    $rejected->id => 'reject',
                ],
                'deactivation_reason' => 'El proyecto concluyó.',
            ])
            ->assertRedirect(route('cost_centers.index', ['tab' => 'history']));

        $this->assertFalse($costCenter->refresh()->is_active);
        $this->assertSame('pendiente', $continued->refresh()->status);
        $this->assertSame($step->id, $continued->current_step_id);
        $this->assertSame('rechazado', $rejected->refresh()->status);
        $this->assertNull($rejected->current_step_id);
        $this->assertDatabaseHas('reimbursement_approvals', [
            'reimbursement_id' => $rejected->id,
            'user_id' => $admin->id,
            'action' => 'rechazado',
        ]);
    }

    public function test_deactivation_refuses_to_skip_an_open_reimbursement(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $costCenter = $this->createCostCenter();
        Reimbursement::create([
            'cost_center_id' => $costCenter->id,
            'status' => 'pendiente',
            'total' => 100,
        ]);

        $this->actingAs($admin)
            ->patch(route('cost_centers.toggle_status', $costCenter), [])
            ->assertRedirect(route('cost_centers.deactivation', $costCenter))
            ->assertSessionHas('error');

        $this->assertTrue($costCenter->refresh()->is_active);
    }

    private function createCostCenter(): CostCenter
    {
        $company = Company::create([
            'name' => 'Empresa de prueba ' . uniqid(),
            'account' => '0000000001',
        ]);

        return CostCenter::create([
            'code' => 'CC-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Centro de prueba ' . uniqid(),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
    }
}
