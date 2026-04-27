<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reimbursement;

$drafts = Reimbursement::where('status', 'borrador')->get();
echo "Total Drafts: " . $drafts->count() . "\n";
foreach ($drafts as $d) {
    echo "ID: {$d->id}, User: {$d->user_id}, Week: {$d->week}, CC: {$d->cost_center_id}, Parent: {$d->parent_id}, HasXML: " . ($d->xml_path ? 'Yes' : 'No') . "\n";
}
