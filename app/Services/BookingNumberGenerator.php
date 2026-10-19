<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class BookingNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $year = now('Asia/Manila')->year;

            $setting = Setting::query()
                ->where('setting_key', 'booking_seq')
                ->lockForUpdate()
                ->first();

            if ($setting === null) {
                Setting::setValue('booking_seq', '1');
                $setting = Setting::query()
                    ->where('setting_key', 'booking_seq')
                    ->lockForUpdate()
                    ->first();
            }

            $fromSetting = (int) ($setting?->setting_value ?? '1');
            $sequence = max($fromSetting, $this->highestSequenceForYear($year) + 1);
            $number = sprintf('GK-%d-%04d', $year, $sequence);

            Setting::setValue('booking_seq', (string) ($sequence + 1));

            return $number;
        });
    }

    private function highestSequenceForYear(int $year): int
    {
        $prefix = sprintf('GK-%d-', $year);
        $latest = Booking::query()
            ->withArchived()
            ->where('booking_number', 'like', $prefix.'%')
            ->orderByDesc('booking_number')
            ->value('booking_number');

        if ($latest === null || ! preg_match('/^GK-\d{4}-(\d{4})$/', $latest, $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}
