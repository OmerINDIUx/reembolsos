<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('reimbursements')
                ->where('status', 'pendiente_revision_cxp')
                ->orderBy('id')
                ->chunkById(100, function ($reimbursements) {
                    foreach ($reimbursements as $reimbursement) {
                        $firstMissingStep = DB::table('approval_steps')
                            ->where('cost_center_id', $reimbursement->cost_center_id)
                            ->whereNotExists(function ($approvalQuery) use ($reimbursement) {
                                $approvalQuery->selectRaw('1')
                                    ->from('reimbursement_approvals')
                                    ->where('reimbursement_approvals.reimbursement_id', $reimbursement->id)
                                    ->whereColumn('reimbursement_approvals.step_name', 'approval_steps.name')
                                    ->where('reimbursement_approvals.action', 'aprobado');
                            })
                            ->orderBy('order')
                            ->first();

                        if (!$firstMissingStep) {
                            continue;
                        }

                        DB::table('reimbursements')
                            ->where('id', $reimbursement->id)
                            ->update([
                                'status' => 'pendiente',
                                'current_step_id' => $firstMissingStep->id,
                                'approved_by_cxp_id' => null,
                                'approved_by_cxp_at' => null,
                                'updated_at' => now(),
                            ]);

                        DB::table('reimbursement_approvals')->insert([
                            'reimbursement_id' => $reimbursement->id,
                            'user_id' => null,
                            'step_name' => 'Corrección automática de flujo',
                            'action' => 'ajuste_flujo',
                            'comment' => 'La solicitud regresó a ' . ($firstMissingStep->name ?? 'su aprobación pendiente') . ' porque no existe una aprobación real en el Historial de Movimientos. CXP sólo puede recibir flujos completamente aprobados.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }, 'reimbursements.id', 'id');
        });
    }

    public function down(): void
    {
        // No se adelantan nuevamente solicitudes que fueron corregidas por
        // carecer de una aprobación auditable.
    }
};
