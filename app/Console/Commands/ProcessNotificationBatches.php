<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessNotificationBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimbursements:process-batches';
    protected $description = 'Process and send batched email notifications after the 1-hour buffer';

    public function handle()
    {
        $batches = \App\Models\NotificationBatch::where('send_at', '<=', now())->get();

        if ($batches->isEmpty()) {
            $this->info('No batches to process at this time.');
            return;
        }

        foreach ($batches as $batch) {
            $user = $batch->user;
            $reimbursementIds = $batch->reimbursement_ids;

            $reimbursements = \App\Models\Reimbursement::with('costCenter')
                ->whereIn('id', $reimbursementIds)
                ->get();

            if ($reimbursements->isNotEmpty()) {
                $user->notify(new \App\Notifications\BatchedReimbursementsNotification($reimbursements));
                $this->info("Summary email sent to {$user->email} for " . $reimbursements->count() . " items.");
            }

            $batch->delete();
        }

        $this->info('Finished processing batches.');
    }
}
