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

echo "Reimbursement 77 Status: {$r->status}\n";
foreach($r->approvals as $a) {
    echo "Approval ID: {$a->id}\n";
    echo "  User: {$a->user->name} (ID: {$a->user_id})\n";
    echo "  Step Name: {$a->step_name}\n";
    echo "  Substituted ID: " . ($a->substituted_user_id ?? 'NULL') . "\n";
    if ($a->substituted_user_id) {
        $sub = \App\Models\User::find($a->substituted_user_id);
        echo "  Substituted Name: " . ($sub->name ?? 'Unknown') . "\n";
    }
}
