<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->json('cfdi_conceptos')->nullable()->after('monto_isr');
            $table->json('impuestos_locales')->nullable()->after('cfdi_conceptos');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn(['cfdi_conceptos', 'impuestos_locales']);
        });
    }
};
