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
        // Incluimos todos los estados posibles para evitar el error de "Data truncated"
        DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status ENUM(
            'pendiente', 
            'aprobado', 
            'rechazado', 
            'borrador', 
            'requiere_correccion', 
            'aprobado_director', 
            'aprobado_control', 
            'aprobado_ejecutivo', 
            'aprobado_cxp', 
            'aprobado_direccion', 
            'aprobado_tesoreria', 
            'pagado',
            'en_evento',
            'pendiente_pago'
        ) DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE reimbursements MODIFY COLUMN status ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente'");
    }
};
