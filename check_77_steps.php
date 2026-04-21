<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Reimbursement;

$r = Reimbursement::find(77);
if (!$r) {
    echo "Reimbursement 77 not found.\n";
    exit;
}

echo "Cost Center: " . ($r->costCenter->name ?? 'None') . "\n";
foreach($r->costCenter->approvalSteps as $s) {
    echo "Step: {$s->name} | Assigned User: " . ($s->user->name ?? 'None') . " (ID: {$s->user_id})\n";
}
