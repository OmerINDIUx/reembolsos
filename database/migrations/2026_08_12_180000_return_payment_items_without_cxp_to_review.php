<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Return legacy records that bypassed CXP to the mandatory CXP review queue.
     * Completed payments are intentionally excluded because they are already settled.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $query = DB::table('reimbursements')
                ->whereIn('status', ['pendiente_pago', 'aprobado', 'aprobado_tesoreria'])
                ->whereNull('approved_by_cxp_at');

            $query->orderBy('id')->chunkById(200, function ($reimbursements): void {
                foreach ($reimbursements as $reimbursement) {
                    DB::table('reimbursements')
                        ->where('id', $reimbursement->id)
                        ->update([
                            'status' => 'pendiente_revision_cxp',
                            'current_step_id' => null,
                            'approved_by_treasury_id' => null,
                            'approved_by_treasury_at' => null,
                            'payment_week' => null,
                            'updated_at' => now(),
                        ]);

                    DB::table('reimbursement_approvals')->insert([
                        'reimbursement_id' => $reimbursement->id,
                        'user_id' => null,
                        'step_name' => 'Corrección automática de flujo',
                        'action' => 'ajuste_flujo',
                        'comment' => 'El reembolso fue regresado automáticamente a CXP Revisadores porque llegó a Pago sin aprobación de CXP. Las aprobaciones pendientes deben continuar desde esta etapa.',
                        'is_bulk' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        });
    }

    public function down(): void
    {
        // No se revierten estados de workflow corregidos automáticamente.
    }
};
