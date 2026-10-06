<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Driver = 'driver';
    case Staff = 'staff';
    case SystemAdmin = 'system_admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Driver => 'Driver',
            self::Staff => 'Staff',
            self::SystemAdmin => 'System admin',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Customer => 'neutral',
            self::Driver => 'info',
            self::Staff => 'warning',
            self::SystemAdmin => 'success',
        };
    }
}
