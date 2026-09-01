<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repair production rows created while the dynamic workflow used either of
     * its generic pending statuses. Both values must become a real stage.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'enviado'");
        }

        DB::table('reimbursements')
            ->whereIn('status', ['pendiente', 'pendiente_autorizacion'])
            ->update(['status' => DB::raw("CASE
                WHEN approved_by_direccion_at IS NOT NULL THEN 'aprobado_direccion'
                WHEN approved_by_executive_at IS NOT NULL THEN 'aprobado_ejecutivo'
                WHEN approved_by_control_at IS NOT NULL THEN 'aprobado_control'
                WHEN approved_by_director_at IS NOT NULL THEN 'aprobado_director'
                ELSE 'enviado'
            END")]);
    }

    public function down(): void
    {
        // This repair intentionally preserves the more precise state reached
        // by every reimbursement, so it is not reverted automatically.
    }
};
