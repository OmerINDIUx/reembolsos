<?php

namespace App\Console\Commands;

use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\PendingApprovalsReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPendingApprovalsReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reimbursements:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly email reminders to users with pending reimbursements to approve';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pending approvals reminders...');

        // 1. Get reimbursements pending a specific step
        $pendingByStep = Reimbursement::whereNotNull('current_step_id')
            ->whereNotIn('status', ['aprobado', 'rechazado', 'borrador', 'requiere_correccion'])
            ->with(['currentStep.user', 'costCenter'])
            ->get();

        $notificationsCount = 0;
        $usersToNotify = [];

        foreach ($pendingByStep as $reimbursement) {
            $approver = $reimbursement->currentStep->user ?? null;
            if ($approver) {
                $uid = $approver->id;
                $usersToNotify[$uid]['user'] = $approver;
                $usersToNotify[$uid]['count'] = ($usersToNotify[$uid]['count'] ?? 0) + 1;
                $usersToNotify[$uid]['total'] = ($usersToNotify[$uid]['total'] ?? 0) + (float)$reimbursement->total;
                
                $ccName = $reimbursement->costCenter->name ?? 'Sin Centro de Costos';
                if (!isset($usersToNotify[$uid]['breakdown'][$ccName])) {
                    $usersToNotify[$uid]['breakdown'][$ccName] = ['count' => 0, 'total' => 0];
                }
                $usersToNotify[$uid]['breakdown'][$ccName]['count']++;
                $usersToNotify[$uid]['breakdown'][$ccName]['total'] += (float)$reimbursement->total;
            }
        }

        // 2. Special case: pendiente_pago (CXP users)
        $pendingPaymentItems = Reimbursement::where('status', 'pendiente_pago')->with('costCenter')->get();
        if ($pendingPaymentItems->count() > 0) {
            $cxpUsers = User::where('role', 'accountant')->get();
            foreach ($cxpUsers as $cxp) {
                $uid = $cxp->id;
                $usersToNotify[$uid]['user'] = $cxp;
                foreach ($pendingPaymentItems as $r) {
                    $usersToNotify[$uid]['count'] = ($usersToNotify[$uid]['count'] ?? 0) + 1;
                    $usersToNotify[$uid]['total'] = ($usersToNotify[$uid]['total'] ?? 0) + (float)$r->total;
                    
                    $ccName = $r->costCenter->name ?? 'Sin Centro de Costos';
                    if (!isset($usersToNotify[$uid]['breakdown'][$ccName])) {
                        $usersToNotify[$uid]['breakdown'][$ccName] = ['count' => 0, 'total' => 0];
                    }
                    $usersToNotify[$uid]['breakdown'][$ccName]['count']++;
                    $usersToNotify[$uid]['breakdown'][$ccName]['total'] += (float)$r->total;
                }
            }
        }

        // 3. Send notifications
        foreach ($usersToNotify as $data) {
            $user = $data['user'];
            $count = $data['count'];
            $total = $data['total'] ?? 0;
            $breakdown = $data['breakdown'] ?? [];

            try {
                $user->notify(new PendingApprovalsReminder($count, $total, $breakdown));
                $this->info("Notified {$user->name} about {$count} pending tasks ($" . number_format($total, 2) . ").");
                $notificationsCount++;
            } catch (\Exception $e) {
                $this->error("Failed to notify {$user->name}: " . $e->getMessage());
                Log::error("Reminder notification failed for {$user->name}: " . $e->getMessage());
            }
        }

        $this->info("Finished. Total notifications sent: {$notificationsCount}");
    }
}
