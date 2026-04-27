<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reimbursement;
use Illuminate\Support\Facades\DB;

$drafts = Reimbursement::where('status', 'borrador')
    ->whereNull('parent_id')
    ->orderBy('user_id')
    ->orderBy('week')
    ->orderBy('cost_center_id')
    ->orderBy('created_at')
    ->get();

$grouped = $drafts->groupBy(function($item) {
    // Group by User, Week, CC and whether it has XML (Invoice) or not
    $hasXml = $item->xml_path ? 'xml' : 'manual';
    return "{$item->user_id}_{$item->week}_{$item->cost_center_id}_{$hasXml}";
});

$fixedCount = 0;

foreach ($grouped as $key => $items) {
    if ($items->count() <= 1) continue;

    // Split into chunks of 6 as requested
    $chunks = $items->chunk(6);

    foreach ($chunks as $chunk) {
        if ($chunk->count() <= 1) continue;

        // The first item in the chunk becomes the PARENT
        $parent = $chunk->shift();
        
        foreach ($chunk as $child) {
            $child->parent_id = $parent->id;
            $child->save();
            $fixedCount++;
            echo "Linked Draft {$child->id} to Parent {$parent->id} (User: {$parent->user_id}, Week: {$parent->week})\n";
        }
    }
}

echo "\nFinished! Linked {$fixedCount} orphan drafts into groups.\n";
