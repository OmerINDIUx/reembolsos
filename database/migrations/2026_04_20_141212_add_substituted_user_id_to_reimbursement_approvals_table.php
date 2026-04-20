<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->foreignId('substituted_user_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->dropForeign(['substituted_user_id']);
            $table->dropColumn('substituted_user_id');
        });
    }
};
