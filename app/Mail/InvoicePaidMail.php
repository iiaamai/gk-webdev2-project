<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $number = $this->invoice->booking?->booking_number ?? 'booking';

        return new Envelope(
            subject: 'Invoice paid — '.$number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-paid',
        );
    }
}
