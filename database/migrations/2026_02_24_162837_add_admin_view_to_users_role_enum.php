<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'admin_view', 'director', 'accountant', 'user', 'tesoreria', 'control_obra', 'director_ejecutivo') DEFAULT 'user' COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'director', 'accountant', 'user', 'tesoreria', 'control_obra', 'director_ejecutivo') DEFAULT 'user' COLLATE utf8mb4_unicode_ci");
    }
};
