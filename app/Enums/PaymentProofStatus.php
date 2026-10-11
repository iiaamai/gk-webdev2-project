<?php

namespace App\Enums;

enum PaymentProofStatus: string
{
    case Pending = 'pending';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting review',
            self::Rejected => 'Did not reflect',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Rejected => 'danger',
        };
    }
}
