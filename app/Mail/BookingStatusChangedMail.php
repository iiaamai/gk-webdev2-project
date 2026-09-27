<?php

namespace App\Mail;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingStatus $status,
        public string $audience = 'customer',
    ) {}

    public function envelope(): Envelope
    {
        $label = match ($this->status) {
            BookingStatus::Accepted => 'accepted',
            BookingStatus::InTransit => 'in transit',
            BookingStatus::Completed => 'completed',
            BookingStatus::Cancelled => 'cancelled',
            default => $this->status->value,
        };

        return new Envelope(
            subject: 'Booking '.$label.' — '.$this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.booking-status-changed',
        );
    }
}
