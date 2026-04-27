<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(3);
echo "User 3: " . ($user ? $user->name . " (Role: " . $user->role . ")" : "Not found") . "\n";

$reimb = App\Models\Reimbursement::find(13);
if ($reimb) {
    echo "Reimbursement 13 Details:\n";
    echo "  Status: {$reimb->status}\n";
    echo "  Current Step: " . ($reimb->currentStep ? $reimb->currentStep->name : 'N/A') . "\n";
    echo "  Created At: {$reimb->created_at}\n";
    echo "  Cost Center ID: {$reimb->cost_center_id}\n";
    echo "  CC Approvers: " . ($reimb->costCenter ? $reimb->costCenter->approvalSteps()->count() : 0) . " steps\n";
}
