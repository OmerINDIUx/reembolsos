<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;

$ccId = 2;
$cc = CostCenter::find($ccId);
if (!$cc) {
    die("Cost Center $ccId not found\n");
}

echo "Cost Center: {$cc->name} (ID: {$cc->id})\n";
echo "Budget: {$cc->budget}\n";

$authorizedUsers = DB::table('cost_center_user')
    ->where('cost_center_id', $ccId)
    ->join('users', 'users.id', '=', 'cost_center_user.user_id')
    ->select('users.id', 'users.name', 'cost_center_user.can_do_special')
    ->get();

echo "\nAuthorized Users in this CC:\n";
foreach ($authorizedUsers as $au) {
    echo "- User: {$au->name} (ID: {$au->id}), Can Do Special: " . ($au->can_do_special ? 'YES' : 'NO') . "\n";
}

$reimbursements = $cc->reimbursements()
    ->whereNotIn('status', ['borrador'])
    ->with('user')
    ->get();

echo "\nReimbursements in this CC (non-draft):\n";
foreach ($reimbursements as $r) {
    echo "- Folio: {$r->folio}, User: {$r->user->name} (ID: {$r->user_id}), Type: {$r->type}, Status: {$r->status}, Total: {$r->total}, Travel Event ID: " . ($r->travel_event_id ?: 'NULL') . "\n";
}
