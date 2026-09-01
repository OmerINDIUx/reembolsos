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
        // Change status column from ENUM to String using raw SQL (avoid doctrine/dbal)
        DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status VARCHAR(255) DEFAULT 'pendiente'");

        Schema::table('reimbursements', function (Blueprint $table) {
            // Add Director Approval Info
            $table->foreignId('approved_by_director_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_by_director_at')->nullable();

            // Add Accounts Payable (CXP) Approval Info
            $table->foreignId('approved_by_cxp_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_by_cxp_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign(['approved_by_director_id']);
            $table->dropForeign(['approved_by_cxp_id']);
            $table->dropColumn(['approved_by_director_id', 'approved_by_director_at', 'approved_by_cxp_id', 'approved_by_cxp_at']);
        });
        
        // Revert status to ENUM (optional, but good practice. Might be tricky without doctrine or raw sql)
        // DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente'");
    }
};
