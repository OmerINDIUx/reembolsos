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
        // 1. Update User Roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'director', 'accountant', 'user', 'tesoreria', 'control_obra', 'director_ejecutivo') DEFAULT 'user' COLLATE utf8mb4_unicode_ci");

        // 2. Update Cost Centers
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreignId('control_obra_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('director_ejecutivo_id')->nullable()->constrained('users')->onDelete('set null');
        });

        // 3. Update Reimbursements with new approval steps
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->foreignId('approved_by_control_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_by_control_at')->nullable();
            
            $table->foreignId('approved_by_executive_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_by_executive_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign(['approved_by_control_id']);
            $table->dropColumn(['approved_by_control_id', 'approved_by_control_at']);
            $table->dropForeign(['approved_by_executive_id']);
            $table->dropColumn(['approved_by_executive_id', 'approved_by_executive_at']);
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropForeign(['control_obra_id']);
            $table->dropForeign(['director_ejecutivo_id']);
            $table->dropColumn(['control_obra_id', 'director_ejecutivo_id']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'director', 'accountant', 'user', 'tesoreria') DEFAULT 'user' COLLATE utf8mb4_unicode_ci");
    }
};
