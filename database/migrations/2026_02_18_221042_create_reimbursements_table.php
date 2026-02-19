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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->nullable()->unique(); // CFDI UUID
            $table->string('rfc_emisor', 13)->nullable();
            $table->string('nombre_emisor')->nullable();
            $table->string('rfc_receptor', 13)->nullable();
            $table->string('nombre_receptor')->nullable();
            $table->string('folio')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('impuestos', 10, 2)->nullable();
            $table->string('moneda', 3)->nullable();
            $table->string('tipo_comprobante', 1)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
