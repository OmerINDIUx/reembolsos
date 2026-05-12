<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 21;
$reimbursement = App\Models\Reimbursement::with('children')->find($id);

if ($reimbursement) {
    echo json_encode($reimbursement->toArray(), JSON_PRETTY_PRINT);
} else {
    echo "Reimbursement not found";
}
