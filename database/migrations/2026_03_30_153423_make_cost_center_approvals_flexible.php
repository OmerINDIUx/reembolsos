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
        Schema::table('cost_centers', function (Blueprint $table) {
            // Make original director_id nullable if it wasn't
            $table->foreignId('director_id')->nullable()->change();
            
            // Add other general approval levels to CostCenter level
            $table->foreignId('accountant_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('direccion_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('tesoreria_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropForeign(['accountant_id']);
            $table->dropForeign(['direccion_id']);
            $table->dropForeign(['tesoreria_id']);
            $table->dropColumn(['accountant_id', 'direccion_id', 'tesoreria_id']);
            
            $table->foreignId('director_id')->nullable(false)->change();
        });
    }
};
