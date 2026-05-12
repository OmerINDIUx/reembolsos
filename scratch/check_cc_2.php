<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cc = App\Models\CostCenter::with('authorizedUsers')->find(2);
echo "CC Name: " . $cc->name . "\n";
echo "CC Budget: " . $cc->budget . "\n";
echo "Authorized Users Count: " . $cc->authorizedUsers->count() . "\n";
foreach($cc->authorizedUsers as $u) {
    echo "- User: " . $u->name . " | Special: " . ($u->pivot->can_do_special ? 'YES' : 'NO') . "\n";
}

$reimbursements = App\Models\Reimbursement::where('cost_center_id', 2)->get();
echo "Reimbursements Count: " . $reimbursements->count() . "\n";
foreach($reimbursements as $r) {
    echo "- ID: " . $r->id . " | Status: " . $r->status . " | Total: " . $r->total . " | User: " . $r->user_id . "\n";
}
