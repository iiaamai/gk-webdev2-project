<?php

namespace App\Enums;

enum DriverAttendanceStatus: string
{
    case NotTimedIn = 'not_timed_in';
    case TimedIn = 'timed_in';
    case TimedOut = 'timed_out';

    public function label(): string
    {
        return match ($this) {
            self::NotTimedIn => 'Not timed in',
            self::TimedIn => 'Timed in',
            self::TimedOut => 'Timed out',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::NotTimedIn => 'neutral',
            self::TimedIn => 'success',
            self::TimedOut => 'warning',
        };
    }
}
