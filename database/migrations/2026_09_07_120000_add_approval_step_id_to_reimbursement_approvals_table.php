<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->foreignId('approval_step_id')
                ->nullable()
                ->after('reimbursement_id')
                ->constrained('approval_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->dropForeign(['approval_step_id']);
            $table->dropColumn('approval_step_id');
        });
    }
};
