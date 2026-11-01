<?php

namespace App\Actions;

use App\Models\DriverAttendanceLog;
use App\Models\User;
use App\Support\DriverAttendance as DriverAttendanceSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeInDriver
{
    public function execute(User $driver, User $recordedBy): DriverAttendanceLog
    {
        if (! $driver->isDriver()) {
            throw ValidationException::withMessages([
                'attendance' => 'Attendance applies to driver accounts only.',
            ]);
        }

        if ($driver->isArchived()) {
            throw ValidationException::withMessages([
                'attendance' => 'This driver account is archived.',
            ]);
        }

        if (DriverAttendanceSupport::isTimedIn($driver)) {
            throw ValidationException::withMessages([
                'attendance' => 'This driver is already timed in.',
            ]);
        }

        return DB::transaction(function () use ($driver, $recordedBy): DriverAttendanceLog {
            return DriverAttendanceLog::query()->create([
                'driver_id' => $driver->id,
                'timed_in_at' => now('Asia/Manila'),
                'timed_in_by_id' => $recordedBy->id,
            ]);
        });
    }
}
