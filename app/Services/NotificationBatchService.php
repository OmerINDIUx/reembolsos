<?php

namespace App\Services;

use App\Models\NotificationBatch;
use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\BatchedReimbursementsNotification;
use App\Notifications\ReimbursementNotification;

class NotificationBatchService
{
    private static $batchesCache = [];

    public static function add(User $user, Reimbursement $reimbursement): void
    {
        if (self::isCxpQueueTransition($reimbursement)) {
            $message = $reimbursement->status === 'pendiente_revision_cxp'
                ? 'El reembolso terminó sus aprobaciones y pasó a la cola de CXP Revisadores. No se envió correo por este cambio.'
                : 'CXP terminó la revisión y el reembolso pasó a CXP Pagadores. Todavía no está disponible en el Módulo de Pago y no se envió correo por este cambio.';

            $user->notify(new ReimbursementNotification($reimbursement, $message, 'info', false));
            return;
        }

        $batch = self::$batchesCache[$user->id] ?? null;

        if (! $batch) {
            $batch = NotificationBatch::where('user_id', $user->id)
                ->where('send_at', '>', now())
                ->first();
        }

        if ($batch) {
            $ids = $batch->reimbursement_ids;
            if (! in_array($reimbursement->id, $ids)) {
                $ids[] = $reimbursement->id;
                $batch->update(['reimbursement_ids' => $ids]);
            }
        } else {
            $batch = NotificationBatch::create([
                'user_id' => $user->id,
                'reimbursement_ids' => [$reimbursement->id],
                'send_at' => now()->addMinutes(5),
            ]);
        }

        self::$batchesCache[$user->id] = $batch;
    }

    public static function process(): void
    {
        $batches = NotificationBatch::all();

        foreach ($batches as $batch) {
            $user = $batch->user;
            if (! $user) {
                $batch->delete();
                continue;
            }

            $reimbursements = Reimbursement::with(['costCenter', 'currentStep', 'user', 'payee', 'createdBy'])
                ->whereIn('id', $batch->reimbursement_ids)
                ->get();

            $queueItems = $reimbursements->filter(fn (Reimbursement $reimbursement) => self::isCxpQueueTransition($reimbursement));
            foreach ($queueItems as $reimbursement) {
                $message = $reimbursement->status === 'pendiente_revision_cxp'
                    ? 'El reembolso está en la cola de CXP Revisadores. Este cambio se informa únicamente dentro del sistema.'
                    : 'El reembolso está en la cola de CXP Pagadores y aún no entra al Módulo de Pago. Este cambio se informa únicamente dentro del sistema.';
                $user->notify(new ReimbursementNotification($reimbursement, $message, 'info', false));
            }

            $reimbursements
                ->reject(fn (Reimbursement $reimbursement) => self::isCxpQueueTransition($reimbursement))
                ->groupBy(fn (Reimbursement $reimbursement) => self::categoryFor($reimbursement))
                ->each(function ($items, string $category) use ($user): void {
                    if ($items->isEmpty()) {
                        return;
                    }

                    try {
                        $user->notify(new BatchedReimbursementsNotification($items->values(), $category));
                        \Log::info("NotificationBatchService: Sent {$category} batch to {$user->email} for {$items->count()} items.");
                    } catch (\Exception $e) {
                        \Log::error("NotificationBatchService: Error sending to {$user->email}: " . $e->getMessage());
                    }
                });

            $batch->delete();
        }

        self::$batchesCache = [];
    }

    public static function categoryFor(Reimbursement $reimbursement): string
    {
        return ($reimbursement->status === 'pendiente_pago' && $reimbursement->approved_by_treasury_at !== null)
            || $reimbursement->status === 'pagado'
                ? 'payment'
                : 'workflow';
    }

    public static function isCxpQueueTransition(Reimbursement $reimbursement): bool
    {
        return $reimbursement->status === 'pendiente_revision_cxp'
            || ($reimbursement->status === 'pendiente_pago' && $reimbursement->approved_by_treasury_at === null);
    }
}
