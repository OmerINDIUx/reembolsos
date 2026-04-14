<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\CostCenter;
use App\Models\Reimbursement;

$email = 'usuariop@example.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found: $email\n";
    exit;
}

echo "User: {$user->name} (ID: {$user->id}, Role: {$user->role})\n";

$indilabCCs = CostCenter::where('name', 'like', '%indilab%')
    ->orWhere('code', 'like', '%indilab%')
    ->get();

if ($indilabCCs->isEmpty()) {
    echo "No cost centers found matching 'indilab'\n";
} else {
    foreach ($indilabCCs as $cc) {
        echo "Cost Center: {$cc->name} (ID: {$cc->id}, Code: {$cc->code})\n";
        
        $pivot = $user->authorizedCostCenters()->where('cost_center_id', $cc->id)->first();
        if ($pivot) {
            echo "  - Authorized: Yes\n";
            echo "  - Can Do Special: " . ($pivot->pivot->can_do_special ? 'True' : 'False') . "\n";
        } else {
            echo "  - Authorized: No\n";
        }
        
        $activeReimbursements = Reimbursement::where('cost_center_id', $cc->id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'rechazado')
            ->get();
            
        if ($activeReimbursements->isNotEmpty()) {
            echo "  - Active Reimbursements (non-rejected): " . $activeReimbursements->count() . "\n";
            foreach ($activeReimbursements as $r) {
                echo "    - ID: {$r->id}, Status: {$r->status}, Type: {$r->type}, Folio: {$r->folio}\n";
            }
        } else {
            echo "  - Active Reimbursements: None\n";
        }
    }
}
