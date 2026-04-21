<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Reimbursement;

$r = Reimbursement::find(83);
if (!$r) {
    echo "Reimbursement 83 not found.\n";
    exit;
}

echo "Reimbursement 83 Status: {$r->status}\n";
echo "Current Step ID: " . ($r->current_step_id ?? 'NULL') . "\n";
foreach($r->costCenter->approvalSteps as $s) {
    echo "Step: {$s->name} | Order: {$s->order} | Assigned: {$s->user->name} (ID: {$s->user_id})\n";
}

echo "\nApprovals History:\n";
foreach($r->approvals as $a) {
    echo "ID: {$a->id} | Step: {$a->step_name} | User: {$a->user->name}\n";
}
