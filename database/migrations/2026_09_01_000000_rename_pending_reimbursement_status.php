<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the ambiguous legacy status with the name of the actual stage.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'pendiente_autorizacion'");
        }

        DB::table('reimbursements')
            ->where('status', 'pendiente')
            ->update(['status' => 'pendiente_autorizacion']);
    }

    public function down(): void
    {
        DB::table('reimbursements')
            ->where('status', 'pendiente_autorizacion')
            ->update(['status' => 'pendiente']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'pendiente'");
        }
    }
};
