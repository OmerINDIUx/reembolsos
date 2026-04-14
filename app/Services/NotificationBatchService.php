<?php

namespace App\Services;

use App\Models\NotificationBatch;
use App\Models\User;
use App\Models\Reimbursement;

class NotificationBatchService
{
    private static $batchesCache = [];

    /**
     * Add a reimbursement to the user's notification batch.
     * Starts a 1-hour timer if no batch exists.
     */
    public static function add(User $user, Reimbursement $reimbursement)
    {
        // Check in-memory cache first for this request
        $batch = self::$batchesCache[$user->id] ?? null;

        if (!$batch) {
            // Check DB if not in cache
            $batch = NotificationBatch::where('user_id', $user->id)
                ->where('send_at', '>', now())
                ->first();
        }

        if ($batch) {
            $ids = $batch->reimbursement_ids;
            if (!in_array($reimbursement->id, $ids)) {
                $ids[] = $reimbursement->id;
                $batch->update(['reimbursement_ids' => $ids]);
            }
        } else {
            $batch = NotificationBatch::create([
                'user_id' => $user->id,
                'reimbursement_ids' => [$reimbursement->id],
                'send_at' => now()->addMinutes(5), // 5-minute buffer to collect bulk actions
            ]);
        }

        self::$batchesCache[$user->id] = $batch;
    }

    /**
     * Process and send all pending batches immediately.
     */
    public static function process()
    {
        $batches = NotificationBatch::all(); // Process ALL batches for all users during a flush

        foreach ($batches as $batch) {
            $user = $batch->user;
            if (!$user) {
                $batch->delete();
                continue;
            }

            $reimbursements = Reimbursement::with('costCenter')
                ->whereIn('id', $batch->reimbursement_ids)
                ->get();

            if ($reimbursements->isNotEmpty()) {
                try {
                    $user->notify(new \App\Notifications\BatchedReimbursementsNotification($reimbursements));
                    \Log::info("NotificationBatchService: Sent batch to {$user->email} for {$reimbursements->count()} items.");
                } catch (\Exception $e) {
                    \Log::error("NotificationBatchService: Error sending to {$user->email}: " . $e->getMessage());
                }
            }

            $batch->delete();
        }
    }
}
