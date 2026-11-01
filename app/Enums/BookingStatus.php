<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case TransitToPickup = 'transit_to_pickup';
    case ArrivedAtPickup = 'arrived_at_pickup';
    case InTransit = 'in_transit';
    case TransitCompleted = 'transit_completed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return list<self>
     */
    public static function activeDeliveryStatuses(): array
    {
        return [
            self::Accepted,
            self::TransitToPickup,
            self::ArrivedAtPickup,
            self::InTransit,
            self::TransitCompleted,
        ];
    }

    /**
     * Bookings underway after pickup or on the haul leg (office KPI “On trip”).
     *
     * @return list<self>
     */
    public static function onTripStatuses(): array
    {
        return [
            self::ArrivedAtPickup,
            self::InTransit,
            self::TransitCompleted,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::TransitToPickup => 'Transit to pickup',
            self::ArrivedAtPickup => 'Arrived at pickup',
            self::InTransit => 'Haul in progress',
            self::TransitCompleted => 'Transit completed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted, self::TransitToPickup, self::ArrivedAtPickup, self::InTransit, self::TransitCompleted => 'info',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
