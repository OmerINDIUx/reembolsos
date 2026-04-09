<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$jobsCount = DB::table('jobs')->count();
$failedJobsCount = DB::table('failed_jobs')->count();
$batches = App\Models\NotificationBatch::all();
$reimbursements = App\Models\Reimbursement::where('created_at', '>=', now()->subHours(2))->get();

echo "Pending Jobs in DB: " . $jobsCount . "\n";
echo "Failed Jobs in DB: " . $failedJobsCount . "\n";
echo "Total Batches: " . $batches->count() . "\n";
foreach($batches as $b) {
    echo "Batch ID: " . $b->id . ", User: " . $b->user_id . ", SendAt: " . $b->send_at . ", ProcessedAt: " . var_export($b->processed_at, true) . "\n";
}


$users = App\Models\User::all();
foreach($users as $u) {
    echo "Email: " . $u->email . ", Role: " . $u->role . "\n";
}

