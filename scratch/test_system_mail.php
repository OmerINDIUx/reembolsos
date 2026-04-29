<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

use App\Mail\UserInvitation;
use App\Models\User;

echo "--- Probando Nuevo Diseño de Correo Premium ---\n";
echo "Mailer actual: " . config('mail.default') . "\n";
echo "Enviando invitación de prueba...\n";

try {
    $user = User::first();
    if (!$user) {
        throw new Exception("No hay usuarios en la base de datos.");
    }
    
    // Forzamos un token para la prueba
    $user->invitation_token = 'token-de-prueba-' . time();

    Mail::to([ 'omer.tenahua@grupoindi.com'])->send(new UserInvitation($user));

    echo "[OK] El sistema envió la invitación con el nuevo diseño. Revisa tu bandeja de entrada.\n";
} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
