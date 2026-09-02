<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->string('archived_uuid', 36)->nullable()->after('uuid');
        });

        // Existing deleted reimbursements must release their operational UUID too.
        // The original value stays available to audit in archived_uuid.
        DB::table('reimbursements')
            ->where('status', 'eliminado')
            ->whereNotNull('uuid')
            ->update([
                'archived_uuid' => DB::raw('uuid'),
                'uuid' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn('archived_uuid');
        });
    }
};
