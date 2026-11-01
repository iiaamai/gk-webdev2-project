<?php

namespace Database\Seeders;

use App\Actions\TimeInDriver;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\DriverAttendance;
use Illuminate\Database\Seeder;

class DriverAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $recordedBy = User::query()->where('email', 'admin@gk.test')->first()
            ?? User::query()->where('role', UserRole::Staff)->first();

        if ($recordedBy === null) {
            return;
        }

        $timeInDriver = app(TimeInDriver::class);

        User::query()
            ->where('role', UserRole::Driver)
            ->orderBy('id')
            ->each(function (User $driver) use ($timeInDriver, $recordedBy): void {
                if (DriverAttendance::isTimedIn($driver)) {
                    return;
                }

                $timeInDriver->execute($driver, $recordedBy);
            });
    }
}
