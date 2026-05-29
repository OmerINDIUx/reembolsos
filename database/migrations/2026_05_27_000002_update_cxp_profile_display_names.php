<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('profiles')->where('name', 'accountant')->update([
            'display_name' => 'Cuentas por Pagar Revisador',
            'description' => 'Revisa reembolsos después del flujo de aprobadores y antes de enviarlos a pago.',
        ]);

        DB::table('profiles')->where('name', 'tesoreria')->update([
            'display_name' => 'Cuentas por Pagar Pagador',
            'description' => 'Autoriza el pago final de reembolsos revisados por Cuentas por Pagar.',
        ]);
    }

    public function down(): void
    {
        DB::table('profiles')->where('name', 'accountant')->update([
            'display_name' => 'Cuentas por Pagar N4',
            'description' => null,
        ]);

        DB::table('profiles')->where('name', 'tesoreria')->update([
            'display_name' => 'Dirección N6',
            'description' => null,
        ]);
    }
};
