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
            $table->string('ticket_path')->nullable()->after('pdf_path');
            $table->string('metodo_pago')->nullable()->after('tipo_comprobante');
            $table->string('forma_pago')->nullable()->after('metodo_pago');
            $table->string('uso_cfdi')->nullable()->after('forma_pago');
            $table->string('lugar_expedicion')->nullable()->after('uso_cfdi');
            $table->string('regimen_fiscal_emisor')->nullable()->after('lugar_expedicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn(['ticket_path', 'metodo_pago', 'forma_pago', 'uso_cfdi', 'lugar_expedicion', 'regimen_fiscal_emisor']);
        });
    }
};
