<?php

namespace App\Mail;

use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReimbursementClarificationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reimbursement $reimbursement,
        public User $requestedBy,
        public User $recipient,
    ) {
        foreach (['user', 'costCenter', 'currentStep'] as $relation) {
            if (!$this->reimbursement->relationLoaded($relation)) {
                $this->reimbursement->load($relation);
            }
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de aclaración: ' . $this->reimbursement->true_folio,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reimbursement-clarification-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
