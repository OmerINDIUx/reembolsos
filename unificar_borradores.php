<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reimbursement;

echo "Iniciando unificación de borradores en Producción...\n";

$drafts = Reimbursement::where('status', 'borrador')
    ->whereNull('parent_id')
    ->orderBy('user_id')
    ->orderBy('week')
    ->orderBy('cost_center_id')
    ->orderBy('created_at')
    ->get();

$grouped = $drafts->groupBy(function($item) {
    $hasXml = $item->xml_path ? 'xml' : 'manual';
    return "{$item->user_id}_{$item->week}_{$item->cost_center_id}_{$hasXml}";
});

$fixedCount = 0;

foreach ($grouped as $items) {
    if ($items->count() <= 1) continue;

    $chunks = $items->chunk(6);

    foreach ($chunks as $chunk) {
        if ($chunk->count() <= 1) continue;

        $parent = $chunk->shift();
        
        foreach ($chunk as $child) {
            $child->parent_id = $parent->id;
            $child->save();
            $fixedCount++;
            echo "Viculado: Borrador {$child->id} -> Padre {$parent->id}\n";
        }
    }
}

echo "\nFinalizado: Se unificaron {$fixedCount} borradores sueltos.\n";
