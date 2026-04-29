<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$testEmail = 'omer.tenahua@grupoindi.com'; // Adjust if needed

// Forzar configuración para la prueba
config([
    'mail.mailers.smtp.host' => 'smtp.office365.com',
    'mail.mailers.smtp.port' => 587,
    'mail.mailers.smtp.username' => 'no-replay@grupoindi.com',
    'mail.mailers.smtp.password' => 'J(291903043236uj',
    'mail.mailers.smtp.encryption' => 'tls',
    'mail.from.address' => 'no-replay@grupoindi.com',
    'mail.from.name' => 'Sistema de Reembolsos',
]);

echo "Iniciando prueba de correo...\n";
echo "Configuración forzada:\n";
echo "Host: " . config('mail.mailers.smtp.host') . "\n";
echo "Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Username: " . config('mail.mailers.smtp.username') . "\n";
echo "Encryption: " . config('mail.mailers.smtp.encryption') . "\n";

try {
    echo "1. Probando conexión al host " . config('mail.mailers.smtp.host') . " en el puerto " . config('mail.mailers.smtp.port') . "...\n";
    $connection = @fsockopen(config('mail.mailers.smtp.host'), config('mail.mailers.smtp.port'), $errno, $errstr, 5);
    if (!$connection) {
        throw new \Exception("No se pudo conectar al servidor: $errstr ($errno)");
    }
    echo "¡Conexión socket exitosa!\n";
    fclose($connection);

    echo "2. Intentando enviar correo a través de Laravel Mail...\n";
    Mail::raw('Esta es una prueba de correo detallada.', function ($message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Prueba de Correo Detallada');
    });
    echo "¡Éxito! El correo ha sido enviado a $testEmail.\n";
} catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
    echo "\n--- ERROR DE TRANSPORTE ---\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getDebug')) {
        echo "Debug: " . $e->getDebug() . "\n";
    }
} catch (\Exception $e) {
    echo "\n--- ERROR GENERAL ---\n";
    echo "Clase: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
}
