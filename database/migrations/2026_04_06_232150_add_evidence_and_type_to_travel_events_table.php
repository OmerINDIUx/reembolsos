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
        Schema::table('travel_events', function (Blueprint $table) {
            $table->string('approval_evidence_path')->nullable()->after('status');
            $table->string('trip_type')->default('nacional')->after('approval_evidence_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_events', function (Blueprint $table) {
            $table->dropColumn(['approval_evidence_path', 'trip_type']);
        });
    }
};
