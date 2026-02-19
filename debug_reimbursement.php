<?php

use App\Models\Reimbursement;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = Reimbursement::find(1);
if ($r) {
    echo "ID: " . $r->id . "\n";
    echo "Parent ID: " . $r->parent_id . "\n";
    echo "Type: " . $r->type . "\n";
    echo "Children Count: " . $r->children()->count() . "\n";
    if ($r->parent_id == $r->id) {
        echo "WARNING: Self-referencing parent_id found!\n";
    }
} else {
    echo "Reimbursement 1 not found.\n";
}
