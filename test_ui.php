<?php

use Illuminate\Support\Facades\Http;

echo "\n--- SISTEMA DE COMPROBACION DE LA PLATAFORMA ---\n\n";

$url = 'http://127.0.0.1:8000';
$errors = 0;
$success = 0;

function check_status($name, $status, $expected) {
    global $errors, $success;
    if ($status === $expected) {
        echo "[OK] $name (HTTP $status)\n";
        $success++;
    } else {
        echo "[ERROR] $name (HTTP $status, Esperado: $expected)\n";
        $errors++;
    }
}

function check_text($name, $content, $text) {
    global $errors, $success;
    if (strpos($content, $text) !== false) {
        echo "[OK] $name contiene '$text'\n";
        $success++;
    } else {
        echo "[ERROR] $name NO contiene '$text'\n";
        $errors++;
    }
}

// 1. Redireccion raiz a login
$ch = curl_init($url . '/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
// No seguimos redireccion para poder capturar el 302
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

if ($httpcode == 302 && strpos($redirect_url, '/login') !== false) {
    echo "[OK] Redireccion de la raiz al login (Requerimiento de 'pantalla principal debe ser login')\n";
    $success++;
} else {
    echo "[ERROR] Raiz de la API no redirige correctamente a login. Codigo HTTP: $httpcode\n";
    $errors++;
}

// 2. Comprobar que ruta de registro se elimino
$ch = curl_init($url . '/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
check_status("Ruta de registro no existe (404 / 405)", $httpcode >= 400 ? 404 : $httpcode, 404);


// 3. Comprobar Home y Logo
$ch = curl_init($url . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

check_status("Página de Login es accesible", $httpcode, 200);
check_text("Login usa el Logo Animanado", $html, 'GI-animado-completo.gif');
if (strpos($html, '<svg') === false || strpos($html, 'viewBox="0 0 316 316"') === false) {
    echo "[OK] Viejo logo de Laravel no esta en la UI.\n";
    $success++;
} else {
    echo "[ERROR] Viejo logo SVG de Laravel todavïa presente en vista de invitado.\n";
    $errors++;
}

// 4. Comprobar que existe fisicamente la imagen en public
if (file_exists(__DIR__ . '/public/images/GI-animado-completo.gif')) {
    echo "[OK] Archivo de imagen copiado correctamente a public/images/\n";
    $success++;
} else {
    echo "[ERROR] Falta archivo GIF en public/images/ \n";
    $errors++;
}

// 5. Comprobar colores en tailwind.config.js
$tailwind = file_get_contents(__DIR__ . '/tailwind.config.js');
check_text("Tailwind config incluye Azul Marca", $tailwind, '#0066f9');
check_text("Tailwind config incluye Verde Marca", $tailwind, '#00fc00');
check_text("Tailwind config incluye Amarillo Marca", $tailwind, '#ffa608');
check_text("Tailwind config incluye Rojo Marca", $tailwind, '#ff3000');


echo "\n--- RESUMEN ---\n";
echo "Exitosos: $success\n";
echo "Errores: $errors\n";

if ($errors === 0) {
    echo "\n=> TODO FUNCIONA CORRECTAMENTE.\n";
    exit(0);
} else {
    echo "\n=> SE ENCONTRARON PROBLEMAS.\n";
    exit(1);
}
