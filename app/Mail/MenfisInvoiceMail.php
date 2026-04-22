<?php

namespace App\Mail;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MenfisInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reimbursement;

    /**
     * Create a new message instance.
     */
    public function __construct(Reimbursement $reimbursement)
    {
        $this->reimbursement = $reimbursement;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Subject has to be the name of the XML before .xml
        $xmlName = $this->reimbursement->original_xml_name ?? 'factura';
        $subject = pathinfo($xmlName, PATHINFO_FILENAME);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.menfis_invoice',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->reimbursement->xml_path && Storage::exists($this->reimbursement->xml_path)) {
            $attachments[] = Attachment::fromPath(Storage::path($this->reimbursement->xml_path))
                ->as($this->reimbursement->original_xml_name ?? 'factura.xml');
        }

        if ($this->reimbursement->pdf_path && Storage::exists($this->reimbursement->pdf_path)) {
            $xmlName = $this->reimbursement->original_xml_name ?? 'factura.xml';
            $baseName = pathinfo($xmlName, PATHINFO_FILENAME);
            $attachments[] = Attachment::fromPath(Storage::path($this->reimbursement->pdf_path))
                ->as($baseName . '.pdf');
        }

        return $attachments;
    }
}
