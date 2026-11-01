<?php

namespace App\Support;

use App\Enums\DriverAttendanceStatus;
use App\Models\DriverAttendanceLog;
use App\Models\User;

class DriverAttendance
{
    public static function statusFor(User $driver): DriverAttendanceStatus
    {
        if (! $driver->isDriver()) {
            return DriverAttendanceStatus::NotTimedIn;
        }

        if (self::openShiftFor($driver) !== null) {
            return DriverAttendanceStatus::TimedIn;
        }

        $hasAnyLog = DriverAttendanceLog::query()
            ->where('driver_id', $driver->id)
            ->exists();

        return $hasAnyLog
            ? DriverAttendanceStatus::TimedOut
            : DriverAttendanceStatus::NotTimedIn;
    }

    public static function isTimedIn(User $driver): bool
    {
        return self::statusFor($driver) === DriverAttendanceStatus::TimedIn;
    }

    public static function openShiftFor(User $driver): ?DriverAttendanceLog
    {
        if (! $driver->isDriver()) {
            return null;
        }

        return DriverAttendanceLog::query()
            ->where('driver_id', $driver->id)
            ->whereNull('timed_out_at')
            ->latest('timed_in_at')
            ->first();
    }

    public static function loginDeniedMessage(): string
    {
        return 'You must be timed in by the office before you can log in. Contact dispatch when you are ready to start your shift.';
    }

    public static function sessionEndedMessage(): string
    {
        return 'Your shift was ended by the office. Contact dispatch when you are timed in again.';
    }
}
