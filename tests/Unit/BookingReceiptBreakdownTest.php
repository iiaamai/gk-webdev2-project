<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\MultiTripOfficeStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Support\BookingReceiptBreakdown;
use App\Support\CancellationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingReceiptBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_fee_invoice_shows_single_fee_line(): void
    {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Cancelled,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 1500,
            'notes' => CancellationRules::feeNote(),
        ]);

        $lines = BookingReceiptBreakdown::lines($booking->fresh(['invoice', 'pricing']));

        $this->assertCount(1, $lines);
        $this->assertSame(CancellationRules::feeNote(), $lines[0]['label']);
        $this->assertSame(1500.0, $lines[0]['amount']);
    }

    public function test_single_trip_uses_base_fare_line(): void
    {
        $pricing = Pricing::factory()->create([
            'vehicle_type' => '6-wheeler',
            'amount' => 15000,
        ]);
        $booking = Booking::factory()->create([
            'pricing_id' => $pricing->id,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 15000,
        ]);

        $lines = BookingReceiptBreakdown::lines($booking->fresh(['invoice', 'pricing']));

        $this->assertSame('Base fare (6-wheeler)', $lines[0]['label']);
        $this->assertSame(15000.0, $lines[0]['amount']);
        $this->assertSame(15000.0, round(array_sum(array_column($lines, 'amount')), 2));
    }

    public function test_approved_multi_trip_splits_base_and_additional_rounds(): void
    {
        $pricing = Pricing::factory()->create([
            'vehicle_type' => '10-wheeler',
            'amount' => 25000,
        ]);
        $booking = Booking::factory()->create([
            'pricing_id' => $pricing->id,
            'multi_trip_office_status' => MultiTripOfficeStatus::Approved,
            'multi_trip_office_rounds' => 3,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 75000,
        ]);

        $lines = BookingReceiptBreakdown::lines($booking->fresh(['invoice', 'pricing']));

        $this->assertCount(2, $lines);
        $this->assertSame('Additional round trips (2×)', $lines[1]['label']);
        $this->assertSame(75000.0, round(array_sum(array_column($lines, 'amount')), 2));
    }
}
