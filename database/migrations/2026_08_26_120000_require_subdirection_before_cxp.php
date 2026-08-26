<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $subdirectionUserIds = DB::table('users')
            ->leftJoin('profiles', 'profiles.id', '=', 'users.profile_id')
            ->whereNull('users.deleted_at')
            ->where('users.status', 'active')
            ->where(function ($query) {
                $query->where('users.role', 'direccion')
                    ->orWhere('profiles.name', 'direccion');
            })
            ->pluck('users.id')
            ->unique()
            ->values();

        // An automatic assignment is safe only when there is one unambiguous
        // active Subdirección approver. The application requires an explicit
        // selection when more than one exists.
        if ($subdirectionUserIds->count() !== 1) {
            return;
        }

        $subdirectionUserId = $subdirectionUserIds->first();

        DB::transaction(function () use ($subdirectionUserIds, $subdirectionUserId) {
            foreach (DB::table('cost_centers')->pluck('id') as $costCenterId) {
                $subdirectionStep = DB::table('approval_steps')
                    ->where('cost_center_id', $costCenterId)
                    ->whereIn('user_id', $subdirectionUserIds)
                    ->orderBy('order')
                    ->first();

                if (!$subdirectionStep) {
                    $nextOrder = ((int) DB::table('approval_steps')
                        ->where('cost_center_id', $costCenterId)
                        ->max('order')) + 1;

                    $stepId = DB::table('approval_steps')->insertGetId([
                        'cost_center_id' => $costCenterId,
                        'user_id' => $subdirectionUserId,
                        'order' => $nextOrder,
                        'name' => 'Subdirección',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $subdirectionStep = (object) [
                        'id' => $stepId,
                        'name' => 'Subdirección',
                    ];
                }

                $reimbursements = DB::table('reimbursements')
                    ->where('cost_center_id', $costCenterId)
                    ->where('status', 'pendiente_revision_cxp')
                    ->whereNull('current_step_id')
                    ->whereNotExists(function ($query) use ($subdirectionStep) {
                        $query->select(DB::raw(1))
                            ->from('reimbursement_approvals')
                            ->whereColumn('reimbursement_approvals.reimbursement_id', 'reimbursements.id')
                            ->where('reimbursement_approvals.step_name', $subdirectionStep->name)
                            ->where('reimbursement_approvals.action', 'aprobado');
                    })
                    ->pluck('id');

                if ($reimbursements->isEmpty()) {
                    continue;
                }

                DB::table('reimbursements')
                    ->whereIn('id', $reimbursements)
                    ->update([
                        'status' => 'pendiente',
                        'current_step_id' => $subdirectionStep->id,
                        'updated_at' => now(),
                    ]);

                foreach ($reimbursements as $reimbursementId) {
                    DB::table('reimbursement_approvals')->insert([
                        'reimbursement_id' => $reimbursementId,
                        'user_id' => null,
                        'step_name' => 'Corrección automática de flujo',
                        'action' => 'ajuste_flujo',
                        'comment' => 'La solicitud regresó al paso obligatorio de Subdirección porque había llegado a Cuentas por Pagar sin esa aprobación.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // The inserted steps may already have approval history. Removing them or
        // advancing corrected reimbursements would recreate the invalid workflow.
    }
};
