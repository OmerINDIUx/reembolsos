<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill installations where the earlier migration already ran when
        // UUID release was limited to deleted drafts.
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
        // UUIDs cannot be safely restored here because they may have been
        // reused by active reimbursements after deletion.
    }
};
