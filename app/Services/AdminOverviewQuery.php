<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AdminOverviewQuery
{
    private const string TIMEZONE = 'Asia/Manila';

    private const int PENDING_GATEPASS_DAYS = 3;

    /**
     * @return array{
     *     kpis: array{
     *         pending_no_gatepass: int,
     *         ready_for_drivers: int,
     *         in_transit: int,
     *         completed_this_month: int,
     *         unpaid_invoices_count: int,
     *         unpaid_invoices_amount: string,
     *         fleet_available: int,
     *         fleet_in_use: int
     *     },
     *     active_bookings: Collection<int, Booking>,
     *     attention_items: list<array{label: string, booking: Booking}>,
     *     recent_activity: Collection<int, ActivityLog>
     * }
     */
    public function gather(): array
    {
        $now = now(self::TIMEZONE);
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $staleBefore = $now->copy()->subDays(self::PENDING_GATEPASS_DAYS);

        $pendingNoGatepass = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->where(function ($query): void {
                $query->whereNull('gatepass_path')->orWhere('gatepass_path', '');
            })
            ->count();

        $readyForDrivers = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->whereNotNull('gatepass_path')
            ->where('gatepass_path', '!=', '')
            ->whereNull('driver_id')
            ->where('is_locked', false)
            ->count();

        $inTransit = Booking::query()
            ->where('status', BookingStatus::InTransit)
            ->count();

        $completedThisMonth = Booking::query()
            ->where('status', BookingStatus::Completed)
            ->whereRaw('COALESCE(accepted_at, created_at) >= ?', [$monthStart])
            ->whereRaw('COALESCE(accepted_at, created_at) <= ?', [$monthEnd])
            ->count();

        $unpaidInvoicesCount = Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->count();

        $unpaidInvoicesAmount = (float) Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->sum('amount');

        $fleetAvailable = Vehicle::query()
            ->where('status', VehicleStatus::Available)
            ->count();

        $fleetInUse = Vehicle::query()
            ->where('status', VehicleStatus::InUse)
            ->count();

        $activeBookings = Booking::query()
            ->with(['customer', 'driver'])
            ->where(function ($query): void {
                $query->whereIn('status', [BookingStatus::Accepted, BookingStatus::InTransit])
                    ->orWhere(function ($inner): void {
                        $inner->where('status', BookingStatus::Pending)
                            ->whereNotNull('gatepass_path')
                            ->where('gatepass_path', '!=', '');
                    });
            })
            ->orderByRaw("CASE status WHEN 'in_transit' THEN 0 WHEN 'accepted' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $attentionItems = [];

        $stalePending = Booking::query()
            ->with('customer')
            ->where('status', BookingStatus::Pending)
            ->where(function ($query): void {
                $query->whereNull('gatepass_path')->orWhere('gatepass_path', '');
            })
            ->where('created_at', '<', $staleBefore)
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        foreach ($stalePending as $booking) {
            $attentionItems[] = [
                'label' => 'No gatepass after '.self::PENDING_GATEPASS_DAYS.'+ days',
                'booking' => $booking,
            ];
        }

        $awaitingDriver = Booking::query()
            ->with('customer')
            ->where('status', BookingStatus::Pending)
            ->whereNotNull('gatepass_path')
            ->where('gatepass_path', '!=', '')
            ->whereNull('driver_id')
            ->where('is_locked', false)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        foreach ($awaitingDriver as $booking) {
            $attentionItems[] = [
                'label' => 'Gatepass uploaded — awaiting driver accept',
                'booking' => $booking,
            ];
        }

        $recentActivity = ActivityLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'kpis' => [
                'pending_no_gatepass' => $pendingNoGatepass,
                'ready_for_drivers' => $readyForDrivers,
                'in_transit' => $inTransit,
                'completed_this_month' => $completedThisMonth,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'unpaid_invoices_amount' => number_format($unpaidInvoicesAmount, 2, '.', ''),
                'fleet_available' => $fleetAvailable,
                'fleet_in_use' => $fleetInUse,
            ],
            'active_bookings' => $activeBookings,
            'attention_items' => $attentionItems,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * @param  Collection<int, Booking>  $activeBookings
     */
    public function resolveMapBooking(Collection $activeBookings, ?int $requestedId): ?Booking
    {
        if ($requestedId !== null) {
            $selected = $activeBookings->firstWhere('id', $requestedId);

            if ($selected !== null) {
                return $selected;
            }
        }

        $inTransit = $activeBookings->first(
            fn (Booking $booking): bool => $booking->status === BookingStatus::InTransit,
        );

        if ($inTransit !== null) {
            return $inTransit;
        }

        $accepted = $activeBookings->first(
            fn (Booking $booking): bool => $booking->status === BookingStatus::Accepted,
        );

        if ($accepted !== null) {
            return $accepted;
        }

        return $activeBookings->first();
    }

    public function nowInManila(): Carbon
    {
        return now(self::TIMEZONE);
    }
}
