<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EarningsQuery
{
    private const string TIMEZONE = 'Asia/Manila';

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
        $query = $this->completedBookingsWithInvoiceQuery();

        if ($from !== null) {
            $query->whereRaw($this->completedBookingDateExpression().' >= ?', [$from]);
        }

        if ($to !== null) {
            $query->whereRaw($this->completedBookingDateExpression().' <= ?', [$to]);
        }

        $totalCompleted = (clone $query)->count('bookings.id');
        $totalPayout = (float) (clone $query)->sum('invoices.amount');
        $averagePayout = $totalCompleted > 0 ? $totalPayout / $totalCompleted : 0.0;

        $monthExpression = $this->monthBucketExpression();

        $monthly = (clone $query)
            ->selectRaw("{$monthExpression} as month")
            ->selectRaw('COUNT(bookings.id) as bookings')
            ->selectRaw('SUM(invoices.amount) as payout')
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

    /**
     * @return array{
     *     period_label: string,
     *     year: int,
     *     month: int,
     *     kpis: array{
     *         completed_trips: int,
     *         completed_revenue: string,
     *         paid_revenue: string,
     *         average_completed_payout: string
     *     },
     *     labels: list<string>,
     *     daily: array{
     *         completed_revenue: list<float>,
     *         paid_revenue: list<float>,
     *         completed_trips: list<int>
     *     },
     *     has_data: bool
     * }
     */
    public function forMonth(int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1, 0, 0, 0, self::TIMEZONE)->startOfMonth();
        $to = $from->copy()->endOfMonth()->endOfDay();

        $completedQuery = $this->completedBookingsWithInvoiceQuery()
            ->whereRaw($this->completedBookingDateExpression().' >= ?', [$from])
            ->whereRaw($this->completedBookingDateExpression().' <= ?', [$to]);

        $completedTrips = (clone $completedQuery)->count('bookings.id');
        $completedRevenue = (float) (clone $completedQuery)->sum('invoices.amount');
        $averageCompleted = $completedTrips > 0 ? $completedRevenue / $completedTrips : 0.0;

        $paidRevenue = (float) Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $dayExpression = $this->dayBucketExpression($this->completedBookingDateExpression());
        $paidDayExpression = $this->dayBucketExpression('paid_at');

        $completedByDay = (clone $completedQuery)
            ->selectRaw("{$dayExpression} as day")
            ->selectRaw('COUNT(bookings.id) as trips')
            ->selectRaw('SUM(invoices.amount) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $paidByDay = Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw("{$paidDayExpression} as day")
            ->selectRaw('SUM(amount) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $dailyCompletedRevenue = [];
        $dailyPaidRevenue = [];
        $dailyTrips = [];

        $daysInMonth = $from->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateKey = $from->copy()->day($day)->format('Y-m-d');
            $labels[] = (string) $day;

            $completedRow = $completedByDay->get($dateKey);
            $paidRow = $paidByDay->get($dateKey);

            $dailyTrips[] = $completedRow ? (int) $completedRow->trips : 0;
            $dailyCompletedRevenue[] = $completedRow ? (float) $completedRow->revenue : 0.0;
            $dailyPaidRevenue[] = $paidRow ? (float) $paidRow->revenue : 0.0;
        }

        $hasData = $completedTrips > 0
            || $paidRevenue > 0
            || array_sum($dailyCompletedRevenue) > 0
            || array_sum($dailyPaidRevenue) > 0;

        return [
            'period_label' => $from->format('F Y'),
            'year' => $year,
            'month' => $month,
            'kpis' => [
                'completed_trips' => $completedTrips,
                'completed_revenue' => number_format($completedRevenue, 2, '.', ''),
                'paid_revenue' => number_format($paidRevenue, 2, '.', ''),
                'average_completed_payout' => number_format($averageCompleted, 2, '.', ''),
            ],
            'labels' => $labels,
            'daily' => [
                'completed_revenue' => $dailyCompletedRevenue,
                'paid_revenue' => $dailyPaidRevenue,
                'completed_trips' => $dailyTrips,
            ],
            'has_data' => $hasData,
        ];
    }

    /**
     * @return Builder<Booking>
     */
    private function completedBookingsWithInvoiceQuery()
    {
        return Booking::query()
            ->join('invoices', 'invoices.booking_id', '=', 'bookings.id')
            ->where('bookings.status', BookingStatus::Completed)
            ->whereNotNull('invoices.amount');
    }

    private function completedBookingDateExpression(): string
    {
        return 'COALESCE(bookings.accepted_at, bookings.created_at)';
    }

    private function monthBucketExpression(): string
    {
        $date = $this->completedBookingDateExpression();
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$date})"
            : "DATE_FORMAT({$date}, '%Y-%m')";
    }

    private function dayBucketExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', {$column})"
            : "DATE({$column})";
    }
}
