<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Setting;

class BookingReceiptBreakdown
{
    /**
     * Line items for the receipt breakdown; amounts sum to the displayed invoice total.
     *
     * @return list<array{label: string, amount: float}>
     */
    public static function lines(Booking $booking): array
    {
        $booking->loadMissing(['pricing', 'invoice']);

        $total = round((float) ($booking->invoice?->amount ?? $booking->pricing?->amount ?? 0), 2);
        $notes = (string) ($booking->invoice?->notes ?? '');

        if ($total <= 0 && $booking->pricing === null) {
            return [
                ['label' => 'Trip total', 'amount' => 0.0],
            ];
        }

        if (str_contains($notes, 'Cancellation fee')) {
            return [
                ['label' => CancellationRules::feeNote(), 'amount' => $total],
            ];
        }

        $pricing = $booking->pricing;

        if ($pricing === null) {
            return [
                ['label' => 'Trip total', 'amount' => $total],
            ];
        }

        $vehicleType = (string) ($pricing->vehicle_type ?? 'Trip');
        $basePerTrip = BookingMultiTrip::baseInvoiceAmount($pricing);
        $roundTrips = BookingMultiTrip::effectiveRoundTrips($booking);

        $lines = [];

        if ($roundTrips <= 1) {
            $lines[] = [
                'label' => 'Base fare ('.$vehicleType.')',
                'amount' => $basePerTrip,
            ];
        } else {
            $lines[] = [
                'label' => 'Base fare ('.$vehicleType.')',
                'amount' => $basePerTrip,
            ];
            $extraTrips = $roundTrips - 1;
            $lines[] = [
                'label' => 'Additional round trips ('.$extraTrips.'×)',
                'amount' => round($basePerTrip * $extraTrips, 2),
            ];
        }

        $subtotal = round(array_sum(array_column($lines, 'amount')), 2);

        if ($booking->vehicle_type_changed_at !== null) {
            $fee = round($total - $subtotal, 2);
            if ($fee > 0.01) {
                $percent = Setting::getValue('wrong_truck_fee_percent', '10');
                $lines[] = [
                    'label' => 'Vehicle change fee ('.$percent.'%)',
                    'amount' => $fee,
                ];
                $subtotal = round($subtotal + $fee, 2);
            }
        }

        $remainder = round($total - $subtotal, 2);
        if (abs($remainder) >= 0.01) {
            $lines[] = [
                'label' => 'Adjustments',
                'amount' => $remainder,
            ];
        }

        return $lines;
    }
}
