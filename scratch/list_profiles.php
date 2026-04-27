<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profiles = App\Models\Profile::all(['id', 'name', 'display_name']);
foreach ($profiles as $profile) {
    echo "ID: {$profile->id}, Name: {$profile->name}, Display Name: {$profile->display_name}\n";
}
