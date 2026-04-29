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
        $disk = config('filesystems.default', 'public');

        \Log::info("MenfisInvoiceMail: Buscando archivos para reembolso {$this->reimbursement->id} en disco '{$disk}'");

        if ($this->reimbursement->xml_path && \Storage::disk($disk)->exists($this->reimbursement->xml_path)) {
            $attachments[] = Attachment::fromPath(\Storage::disk($disk)->path($this->reimbursement->xml_path))
                ->as($this->reimbursement->original_xml_name ?? 'factura.xml');
            \Log::info("- XML encontrado y adjuntado: " . $this->reimbursement->xml_path);
        } else {
            \Log::warning("- XML NO encontrado en: " . ($this->reimbursement->xml_path ?? 'Ruta vacía'));
        }

        if ($this->reimbursement->pdf_path && \Storage::disk($disk)->exists($this->reimbursement->pdf_path)) {
            $xmlName = $this->reimbursement->original_xml_name ?? 'factura.xml';
            $baseName = pathinfo($xmlName, PATHINFO_FILENAME);
            $attachments[] = Attachment::fromPath(\Storage::disk($disk)->path($this->reimbursement->pdf_path))
                ->as($baseName . '.pdf');
            \Log::info("- PDF encontrado y adjuntado: " . $this->reimbursement->pdf_path);
        } else {
            \Log::warning("- PDF NO encontrado en: " . ($this->reimbursement->pdf_path ?? 'Ruta vacía'));
        }

        return $attachments;
    }
}
