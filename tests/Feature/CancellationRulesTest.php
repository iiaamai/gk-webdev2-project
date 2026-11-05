<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cancel_after_accept_charges_ten_percent_of_invoice(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Accepted,
            'accepted_at' => now('Asia/Manila'),
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 15000.00,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertRedirect(route('customer.bookings.index'));

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame('1500.00', (string) $booking->invoice?->amount);
        $this->assertSame('Cancellation fee (10%).', $booking->invoice?->notes);
    }

    public function test_customer_cancel_pending_with_gatepass_charges_ten_percent(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 25000.00,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $this->assertSame('2500.00', (string) $booking->fresh()->invoice?->amount);
    }

    public function test_customer_cancel_pending_after_created_day_charges_ten_percent(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
            'created_at' => now('Asia/Manila')->subDay(),
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 15000.00,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertRedirect();

        $this->assertSame('1500.00', (string) $booking->fresh()->invoice?->amount);
    }

    public function test_customer_cannot_cancel_transit_completed(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::TransitCompleted,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertForbidden();
    }
}
