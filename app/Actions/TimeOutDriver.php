<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\DriverAttendanceLog;
use App\Models\User;
use App\Support\DriverAttendance as DriverAttendanceSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeOutDriver
{
    public function execute(User $driver, User $recordedBy): DriverAttendanceLog
    {
        if (! $driver->isDriver()) {
            throw ValidationException::withMessages([
                'attendance' => 'Attendance applies to driver accounts only.',
            ]);
        }

        $openShift = DriverAttendanceSupport::openShiftFor($driver);

        if ($openShift === null) {
            throw ValidationException::withMessages([
                'attendance' => 'This driver is not timed in.',
            ]);
        }

        if ($this->hasActiveTrip($driver)) {
            throw ValidationException::withMessages([
                'attendance' => 'Cannot time out while this driver has an accepted or in-transit booking.',
            ]);
        }

        return DB::transaction(function () use ($openShift, $recordedBy): DriverAttendanceLog {
            $openShift->update([
                'timed_out_at' => now('Asia/Manila'),
                'timed_out_by_id' => $recordedBy->id,
            ]);

            return $openShift->fresh(['driver', 'timedInBy', 'timedOutBy']);
        });
    }

    private function hasActiveTrip(User $driver): bool
    {
        return Booking::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', BookingStatus::activeDeliveryStatuses())
            ->exists();
    }
}
