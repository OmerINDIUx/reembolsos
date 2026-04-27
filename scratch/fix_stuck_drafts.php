<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fix reimbursement 13
$reimb = App\Models\Reimbursement::find(13);
if ($reimb && $reimb->status === 'borrador' && $reimb->current_step_id) {
    $reimb->status = 'pendiente';
    $reimb->save();
    echo "Fixed Reimbursement 13: Status set to 'pendiente'\n";
} else {
    echo "Reimbursement 13 not found or doesn't need fixing.\n";
}

// Find any other similar cases (drafts with approval steps)
$others = App\Models\Reimbursement::where('status', 'borrador')
    ->whereNotNull('current_step_id')
    ->get();

foreach ($others as $o) {
    if ($o->id != 13) {
        $o->status = 'pendiente';
        $o->save();
        echo "Fixed Reimbursement {$o->id}: Status set to 'pendiente'\n";
    }
}
