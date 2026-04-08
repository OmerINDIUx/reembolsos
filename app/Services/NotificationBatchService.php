<?php

namespace App\Services;

use App\Models\NotificationBatch;
use App\Models\User;
use App\Models\Reimbursement;

class NotificationBatchService
{
    /**
     * Add a reimbursement to the user's notification batch.
     * Starts a 1-hour timer if no batch exists.
     */
    public static function add(User $user, Reimbursement $reimbursement)
    {
        $batch = NotificationBatch::where('user_id', $user->id)
            ->where('send_at', '>', now())
            ->first();

        if ($batch) {
            $ids = $batch->reimbursement_ids;
            if (!in_array($reimbursement->id, $ids)) {
                $ids[] = $reimbursement->id;
                $batch->update(['reimbursement_ids' => $ids]);
            }
        } else {
            NotificationBatch::create([
                'user_id' => $user->id,
                'reimbursement_ids' => [$reimbursement->id],
                'send_at' => now()->addHour(),
            ]);
        }
    }
}
