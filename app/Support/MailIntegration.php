<?php

namespace App\Support;

/**
 * External Mail integration gate (see docs/development/development_notes.txt).
 * While placeholder: self-register creates a verified user immediately.
 * When enabled: customer/driver accounts are created only after the email link.
 */
class MailIntegration
{
    public static function isEnabled(): bool
    {
        return (bool) config('gk.mail_enabled', false);
    }
}
