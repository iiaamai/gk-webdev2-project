<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class StaffOverviewQuery
{
    private const string TIMEZONE = 'Asia/Manila';

    private const int PENDING_GATEPASS_DAYS = 3;

    /**
     * @return array{
     *     kpis: array{
     *         pending_no_gatepass: int,
     *         ready_for_drivers: int,
     *         in_transit: int,
     *         unpaid_invoices_count: int,
     *         unpaid_invoices_amount: string
     *     },
     *     needs_gatepass: Collection<int, Booking>,
     *     active_bookings: Collection<int, Booking>,
     *     attention_items: list<array{label: string, booking: Booking}>
     * }
     */
    public function gather(): array
    {
        $now = now(self::TIMEZONE);
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

        $unpaidInvoicesCount = Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->count();

        $unpaidInvoicesAmount = (float) Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->sum('amount');

        $needsGatepass = Booking::query()
            ->with('customer')
            ->where('status', BookingStatus::Pending)
            ->where(function ($query): void {
                $query->whereNull('gatepass_path')->orWhere('gatepass_path', '');
            })
            ->orderBy('created_at')
            ->limit(10)
            ->get();

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
            ->limit(15)
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

        return [
            'kpis' => [
                'pending_no_gatepass' => $pendingNoGatepass,
                'ready_for_drivers' => $readyForDrivers,
                'in_transit' => $inTransit,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'unpaid_invoices_amount' => number_format($unpaidInvoicesAmount, 2, '.', ''),
            ],
            'needs_gatepass' => $needsGatepass,
            'active_bookings' => $activeBookings,
            'attention_items' => $attentionItems,
        ];
    }

    public function nowInManila(): Carbon
    {
        return now(self::TIMEZONE);
    }
}
