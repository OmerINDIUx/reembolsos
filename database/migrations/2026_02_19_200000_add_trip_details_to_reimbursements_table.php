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
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->integer('trip_nights')->nullable()->after('location'); // Duración del viaje en noches
            $table->enum('trip_type', ['nacional', 'internacional'])->nullable()->after('trip_nights'); // Destino (Nacional/Internacional)
            $table->string('trip_destination')->nullable()->after('trip_type'); // Lugar de destino
            $table->date('trip_start_date')->nullable()->after('trip_destination'); // Fecha de inicio
            $table->date('trip_end_date')->nullable()->after('trip_start_date'); // Fecha final
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn(['trip_nights', 'trip_type', 'trip_destination', 'trip_start_date', 'trip_end_date']);
        });
    }
};
