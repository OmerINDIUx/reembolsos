<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'usuariop@example.com')->first();
echo "User ID: " . $user->id . "\n";
echo "Authorized Cost Centers:\n";
foreach($user->authorizedCostCenters as $cc) {
    echo $cc->name . " - marked (can_do_special): " . $cc->pivot->can_do_special . "\n";
}

echo "\nActive Reimbursements:\n";
$reims = App\Models\Reimbursement::where('user_id', $user->id)
    ->where('status', '!=', 'rechazado')
    ->get();
foreach($reims as $r) {
    echo "Folio: " . $r->folio . " - CC: " . ($r->costCenter ? $r->costCenter->name : 'N/A') . " - Status: " . $r->status . "\n";
}
