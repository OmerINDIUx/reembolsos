<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->string('payment_week')->nullable()->after('week');
        });

        DB::table('reimbursements')
            ->where('status', 'pendiente_pago')
            ->whereNull('payment_week')
            ->select(['id', 'approved_by_cxp_at', 'updated_at', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($reimbursements) {
                foreach ($reimbursements as $reimbursement) {
                    $baseDate = $reimbursement->approved_by_cxp_at
                        ?? $reimbursement->updated_at
                        ?? $reimbursement->created_at
                        ?? now();

                    DB::table('reimbursements')
                        ->where('id', $reimbursement->id)
                        ->update([
                            'payment_week' => Carbon::parse($baseDate)->addDays(2)->format('W-Y'),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn('payment_week');
        });
    }
};
