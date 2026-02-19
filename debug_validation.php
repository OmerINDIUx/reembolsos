<?php

use App\Models\Reimbursement;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = Reimbursement::orderBy('created_at', 'desc')->first();
if ($r) {
    echo "ID: " . $r->id . "\n";
    echo "Validation Data (Raw): " . json_encode($r->validation_data) . "\n";
    echo "Is Array?: " . (is_array($r->validation_data) ? 'Yes' : 'No') . "\n";
} else {
    echo "No reimbursements found.\n";
}
