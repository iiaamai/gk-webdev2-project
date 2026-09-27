<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EarningsQuery
{
    /**
     * @return array{
     *     total_completed: int,
     *     total_payout: string,
     *     average_payout: string,
     *     monthly: Collection<int, object{month: string, bookings: int, payout: string}>
     * }
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Booking::query()
            ->where('status', BookingStatus::Completed)
            ->whereNotNull('payout');

        if ($from !== null) {
            $query->where('accepted_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('accepted_at', '<=', $to);
        }

        $totalCompleted = (clone $query)->count();
        $totalPayout = (float) (clone $query)->sum('payout');
        $averagePayout = $totalCompleted > 0 ? $totalPayout / $totalCompleted : 0.0;

        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', COALESCE(accepted_at, created_at))"
            : "DATE_FORMAT(COALESCE(accepted_at, created_at), '%Y-%m')";

        $monthly = (clone $query)
            ->selectRaw("{$monthExpression} as month")
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('SUM(payout) as payout')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn (object $row): object => (object) [
                'month' => (string) $row->month,
                'bookings' => (int) $row->bookings,
                'payout' => number_format((float) $row->payout, 2, '.', ''),
            ]);

        return [
            'total_completed' => $totalCompleted,
            'total_payout' => number_format($totalPayout, 2, '.', ''),
            'average_payout' => number_format($averagePayout, 2, '.', ''),
            'monthly' => $monthly,
        ];
    }
}
