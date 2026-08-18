<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_renewals', function (Blueprint $table) {
            $table->foreignId('fixed_fund_id')
                ->nullable()
                ->after('cost_center_id')
                ->constrained('fixed_funds')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_renewals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fixed_fund_id');
        });
    }
};
