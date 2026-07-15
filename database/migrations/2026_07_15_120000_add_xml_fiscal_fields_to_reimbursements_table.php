<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->string('folio_interno_proveedor')->nullable()->after('folio');
            $table->decimal('retencion_iva', 10, 2)->nullable()->default(0)->after('regimen_fiscal_emisor');
            $table->decimal('monto_iva', 10, 2)->nullable()->default(0)->after('retencion_iva');
            $table->decimal('monto_isr', 10, 2)->nullable()->default(0)->after('monto_iva');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn([
                'folio_interno_proveedor',
                'retencion_iva',
                'monto_iva',
                'monto_isr',
            ]);
        });
    }
};
