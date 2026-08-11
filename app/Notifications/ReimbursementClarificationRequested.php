<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use App\Models\User;
use App\Support\NotificationRouteHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReimbursementClarificationRequested extends Notification
{
    use Queueable;

    public function __construct(
        private Reimbursement $reimbursement,
        private User $requestedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->requestedBy->name . ' solicita una aclaración sobre el reembolso ' . $this->reimbursement->true_folio . '.',
            'type' => 'info',
            'reimbursement_id' => $this->reimbursement->id,
            'reimbursement_folio' => $this->reimbursement->true_folio,
            'url' => NotificationRouteHelper::reimbursement($this->reimbursement),
        ];
    }
}
