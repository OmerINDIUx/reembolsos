<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

echo "--- Iniciando Limpieza de Base de Datos de Reembolsos ---\n";

try {
    Schema::disableForeignKeyConstraints();

    echo "Truncando tablas...\n";
    DB::table('reimbursement_approvals')->truncate();
    DB::table('reimbursement_files')->truncate();
    DB::table('notification_batches')->truncate();
    DB::table('reimbursements')->truncate();
    
    // Si existe la tabla de notificaciones de Laravel, también la limpiamos
    if (Schema::hasTable('notifications')) {
        DB::table('notifications')->truncate();
    }

    Schema::enableForeignKeyConstraints();
    echo "[OK] Tablas truncadas exitosamente.\n";

    echo "Limpiando archivos de almacenamiento...\n";
    $folders = ['xmls', 'pdfs', 'tickets', 'reimbursements'];
    foreach ($folders as $folder) {
        if (Storage::exists($folder)) {
            $files = Storage::allFiles($folder);
            foreach ($files as $file) {
                Storage::delete($file);
            }
            echo "- Carpeta '{$folder}' limpiada.\n";
        }
    }
    
    // También revisar en el disco público si existe algo ahí
    if (Storage::disk('public')->exists('tickets')) {
        Storage::disk('public')->deleteDirectory('tickets');
        Storage::disk('public')->makeDirectory('tickets');
        echo "- Carpeta 'public/tickets' limpiada.\n";
    }

    echo "[OK] Archivos eliminados exitosamente.\n";
    echo "\n--- Limpieza Completada. El sistema está listo para pruebas reales. ---\n";

} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
