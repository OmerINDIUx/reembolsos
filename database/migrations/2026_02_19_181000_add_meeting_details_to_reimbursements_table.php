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
            $table->integer('attendees_count')->nullable()->after('observaciones');
            $table->text('attendees_names')->nullable()->after('attendees_count');
            $table->string('location')->nullable()->after('attendees_names'); // lugar
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn(['attendees_count', 'attendees_names', 'location']);
        });
    }
};
