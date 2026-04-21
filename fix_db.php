<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Reimbursement;

echo "Fixing database records...\n";

// 1. Fix UTF-8 mangling in comments
$approvals = DB::table('reimbursement_approvals')->where('comment', 'like', '%Ã%')->get();
foreach ($approvals as $a) {
    $fixed = str_replace(['ÃƒÂ³', 'ÃƒÂ¡', 'ÃƒÂ©', 'ÃƒÂ­', 'ÃƒÂº', 'ÃƒÂ±', 'Ã³', 'Ã¡', 'Ã©', 'Ã­', 'Ãº', 'Ã±'], ['ó', 'á', 'é', 'í', 'ú', 'ñ', 'ó', 'á', 'é', 'í', 'ú', 'ñ'], $a->comment);
    DB::table('reimbursement_approvals')->where('id', $a->id)->update(['comment' => $fixed]);
    echo "Fixed comment for ID {$a->id}\n";
}

// 2. Fix substituted_user_id for record 103 (and others where user_id != step user_id)
$r103 = Reimbursement::find(103);
if ($r103) {
    echo "Fixing Reimbursement 103 approvals...\n";
    foreach ($r103->approvals as $approval) {
        // Find which step this corresponds to
        $step = $r103->costCenter->approvalSteps()->where('name', $approval->step_name)->first();
        if ($step && $step->user_id !== $approval->user_id) {
            $approval->update(['substituted_user_id' => $step->user_id]);
            echo "Updated substituted_user_id for approval {$approval->id}\n";
        }
    }
}

echo "Done.\n";
