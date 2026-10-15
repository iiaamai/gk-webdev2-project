<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class CompleteRegistrationMail extends Mailable
{
    public function __construct(public PendingRegistration $pending) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your GK Trucking Services account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.complete-registration',
            with: [
                'confirmUrl' => URL::temporarySignedRoute(
                    'register.confirm',
                    $this->pending->expires_at,
                    ['pendingRegistration' => $this->pending->id],
                ),
            ],
        );
    }
}
