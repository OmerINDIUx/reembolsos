<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = ['admin', 'user', 'tesoreria', 'direccion', 'accountant', 'director', 'control_obra', 'director_ejecutivo', 'admin_view'];
foreach ($roles as $role) {
    $count = App\Models\User::where('role', $role)->count();
    echo "Role: $role, Count: $count\n";
}
