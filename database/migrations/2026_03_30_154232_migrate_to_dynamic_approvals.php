<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $costCenters = DB::table('cost_centers')->get();
        foreach ($costCenters as $cc) {
            $steps = [
                ['user_id' => $cc->director_id, 'name' => 'Director N1'],
                ['user_id' => $cc->control_obra_id, 'name' => 'Control de Obra N2'],
                ['user_id' => $cc->director_ejecutivo_id, 'name' => 'Director Ejecutivo N3'],
                ['user_id' => $cc->accountant_id, 'name' => 'Cuentas por Pagar (CXP)'],
                ['user_id' => $cc->direccion_id, 'name' => 'Subdirección'],
                ['user_id' => $cc->tesoreria_id, 'name' => 'Dirección'],
            ];

            $order = 1;
            foreach ($steps as $step) {
                if ($step['user_id']) {
                    DB::table('approval_steps')->insert([
                        'cost_center_id' => $cc->id,
                        'user_id' => $step['user_id'],
                        'name' => $step['name'],
                        'order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Map reimbursements
        $reimbursements = DB::table('reimbursements')->whereNotIn('status', ['aprobado', 'rechazado'])->get();
        foreach ($reimbursements as $r) {
            $statusMap = [
                'pendiente' => 1,
                'aprobado_director' => 2,
                'aprobado_control' => 3,
                'aprobado_ejecutivo' => 4,
                'aprobado_cxp' => 5,
                'aprobado_direccion' => 6,
            ];

            if (isset($statusMap[$r->status])) {
                $targetOrder = $statusMap[$r->status];
                $step = DB::table('approval_steps')
                    ->where('cost_center_id', $r->cost_center_id)
                    ->where('order', '>=', $targetOrder)
                    ->orderBy('order', 'asc')
                    ->first();

                if ($step) {
                    DB::table('reimbursements')->where('id', $r->id)->update([
                        'current_step_id' => $step->id
                    ]);
                }
            }
        }
    }
};
