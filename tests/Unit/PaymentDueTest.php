<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CustomerOverviewQuery;
use App\Support\PaymentDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentDueTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_date_is_end_of_fifth_calendar_day_from_creation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 12:00:00', 'Asia/Manila'));

        $booking = Booking::factory()->create([
            'created_at' => Carbon::parse('2026-11-01 08:00:00', 'Asia/Manila'),
        ]);

        $this->assertSame('Nov 5, 2026', PaymentDue::dueDateLabel($booking));
        $this->assertFalse(PaymentDue::isOverdue($booking));
    }

    public function test_unpaid_invoice_is_overdue_after_due_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 00:01:00', 'Asia/Manila'));

        $booking = Booking::factory()->create([
            'created_at' => Carbon::parse('2026-11-01 08:00:00', 'Asia/Manila'),
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->assertTrue(PaymentDue::isOverdue($booking));
    }

    public function test_paid_booking_is_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 00:00:00', 'Asia/Manila'));

        $booking = Booking::factory()->create([
            'created_at' => Carbon::parse('2026-11-01 08:00:00', 'Asia/Manila'),
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Paid,
        ]);

        $this->assertFalse(PaymentDue::isOverdue($booking));
        $this->assertSame('paid', PaymentDue::paymentChip($booking));
    }

    public function test_customer_overview_counts_unpaid_invoices(): void
    {
        $customer = User::factory()->customer()->create();
        $paidBooking = Booking::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create([
            'booking_id' => $paidBooking->id,
            'status' => InvoiceStatus::Paid,
            'amount' => 15000,
        ]);

        $unpaidBooking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
        ]);
        Invoice::factory()->create([
            'booking_id' => $unpaidBooking->id,
            'status' => InvoiceStatus::Unpaid,
            'amount' => 25000,
        ]);

        $overview = app(CustomerOverviewQuery::class)->gather($customer);

        $this->assertSame(1, $overview['unpaid_count']);
        $this->assertSame('25000.00', $overview['unpaid_total']);
    }
}
