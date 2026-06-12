<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursements', 'created_by_id')) {
                $table->foreignId('created_by_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursements', 'created_by_id')) {
                $table->dropForeign(['created_by_id']);
                $table->dropColumn('created_by_id');
            }
        });
    }
};
