<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'pendiente_pago' to the status ENUM
        DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status ENUM('pendiente', 'aprobado', 'rechazado', 'borrador', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo', 'aprobado_direccion', 'aprobado_tesoreria', 'pagado', 'en_evento', 'pendiente_pago') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'pendiente_pago' from the status ENUM
        DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status ENUM('pendiente', 'aprobado', 'rechazado', 'borrador', 'requiere_correccion', 'aprobado_director', 'aprobado_control', 'aprobado_ejecutivo', 'aprobado_direccion', 'aprobado_tesoreria', 'pagado', 'en_evento') DEFAULT 'pendiente'");
    }
};
