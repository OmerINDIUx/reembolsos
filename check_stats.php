<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\Reimbursement;

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stats = Reimbursement::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "Reimbursement Stats by Status:\n";
foreach ($stats as $stat) {
    echo "- {$stat->status}: {$stat->count}\n";
}
