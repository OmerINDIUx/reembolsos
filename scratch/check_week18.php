<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = App\Models\Reimbursement::where('week', 'like', '18-%')->count();
$latest = App\Models\Reimbursement::latest()->first();

echo "Count for Week 18: $count\n";
if ($latest) {
    echo "Latest Reimbursement:\n";
    echo "ID: {$latest->id}\n";
    echo "Folio: {$latest->folio}\n";
    echo "Week: {$latest->week}\n";
    echo "Status: {$latest->status}\n";
    echo "User ID: {$latest->user_id}\n";
}
