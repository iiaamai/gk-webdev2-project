<?php

namespace App\Enums;

enum MultiTripOfficeStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending office review',
            self::Approved => 'Approved',
        };
    }
}
