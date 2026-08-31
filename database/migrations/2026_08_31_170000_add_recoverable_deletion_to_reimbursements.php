<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->string('status_before_deletion')->nullable()->after('status');
            $table->timestamp('deleted_at')->nullable()->index()->after('status_before_deletion');
            $table->foreignId('deleted_by_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign(['deleted_by_id']);
            $table->dropColumn(['status_before_deletion', 'deleted_at', 'deleted_by_id']);
        });
    }
};
