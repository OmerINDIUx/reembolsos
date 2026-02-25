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
        // Add 'direccion' to role ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'admin_view', 'director', 'accountant', 'user', 'tesoreria', 'control_obra', 'director_ejecutivo', 'direccion') DEFAULT 'user' COLLATE utf8mb4_unicode_ci");

        // Add approval columns to reimbursements
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by_direccion_id')->nullable()->after('approved_by_cxp_at');
            $table->timestamp('approved_by_direccion_at')->nullable()->after('approved_by_direccion_id');
            
            $table->foreign('approved_by_direccion_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign(['approved_by_direccion_id']);
            $table->dropColumn(['approved_by_direccion_id', 'approved_by_direccion_at']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'admin_view', 'director', 'accountant', 'user', 'tesoreria', 'control_obra', 'director_ejecutivo') DEFAULT 'user' COLLATE utf8mb4_unicode_ci");
    }
};
