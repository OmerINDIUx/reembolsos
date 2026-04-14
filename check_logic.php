<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'usuariop@example.com')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

$isMarkedInCC = $user->authorizedCostCenters()->wherePivot('can_do_special', true)->exists();
$isMarkedInCC_Alternative = $user->authorizedCostCenters()->where('can_do_special', 1)->exists();

echo "User: " . $user->email . "\n";
echo "isMarkedInCC (true): " . ($isMarkedInCC ? 'true' : 'false') . "\n";
echo "isMarkedInCC (1): " . ($isMarkedInCC_Alternative ? 'true' : 'false') . "\n";

$costCenters = $user->authorizedCostCenters()->withPivot('can_do_special')->get();
foreach($costCenters as $cc) {
    echo "CC: " . $cc->name . ", can_do_special: " . var_export($cc->pivot->can_do_special, true) . "\n";
}
