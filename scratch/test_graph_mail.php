<?php

use App\Services\GraphMailService;

// Cargar el entorno de Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mailService = new GraphMailService();

echo "--- Probando Microsoft Graph (Desde .env) ---\n";
echo "ID Cliente: " . env('GRAPH_CLIENT_ID') . "\n";
echo "ID Inquilino: " . env('GRAPH_TENANT_ID') . "\n";
echo "Enviando correo...\n";

$targetEmail = ''; 

try {
    $result = $mailService->send(
        $targetEmail, 
        'Prueba de Microsoft Graph (Windows Environment)', 
        '<h1>¡Funciona!</h1><p>Este correo es enviado desde el sistema de reembolsos de Grupo Indi v1.50.10.10005</p>'
    );

    if ($result) {
        echo "\n[OK] ¡Éxito! El correo fue aceptado por Microsoft Graph.\n";
    } else {
        echo "\n[ERROR] No se pudo enviar. Revisa los logs en storage/logs/laravel.log\n";
    }
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "\n[ERROR CLIENTE] " . $e->getResponse()->getBody()->getContents() . "\n";
} catch (\Exception $e) {
    echo "\n[ERROR GENERAL] " . $e->getMessage() . "\n";
}

