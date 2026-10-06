<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case InUse = 'in_use';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InUse => 'In use',
            self::Maintenance => 'Maintenance',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::InUse => 'info',
            self::Maintenance => 'warning',
        };
    }
}
