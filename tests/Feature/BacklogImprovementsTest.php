<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklogImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_poll_driver_location_on_completed_booking(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create([
            'location_lat' => 14.6,
            'location_lng' => 121.0,
        ]);
        $booking = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($staff)
            ->getJson(route('staff.bookings.driver-location', $booking))
            ->assertForbidden();
    }

    public function test_admin_cancel_after_accept_charges_twenty_five_percent_cancellation_fee(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create([
            'vehicle_type' => '4-wheeler truck',
            'amount' => 9200.00,
            'capacity_kg' => 3000,
        ]);
        $booking = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
            'status' => BookingStatus::Accepted,
            'accepted_at' => now('Asia/Manila'),
            'is_locked' => true,
            'driver_id' => User::factory()->driver()->create()->id,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9200.00,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking))
            ->assertRedirect();

        $booking->refresh();
        $invoice = $booking->invoice;
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame('2300.00', (string) $invoice?->amount);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice?->status);
        $this->assertSame('Cancellation fee (25%).', $invoice?->notes);
    }

    public function test_admin_cancel_before_accept_keeps_full_invoice_amount(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9200.00,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking))
            ->assertRedirect();

        $this->assertSame('9200.00', (string) $booking->fresh()->invoice?->amount);
    }

    public function test_customer_booking_over_capacity_uses_single_fare_at_create(): void
    {
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create([
            'vehicle_type' => 'L300 van',
            'amount' => 4500,
            'capacity_kg' => 1000,
        ]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.store'), [
                'pricing_id' => $pricing->id,
                'booking_datetime' => now('Asia/Manila')->addDay()->format('Y-m-d H:i:s'),
                'pickup_address' => 'Makati City',
                'pickup_lat' => 14.5547,
                'pickup_lng' => 121.0244,
                'pickup_port_number' => 'PORT-01',
                'pickup_container_number' => 'CONT-ABC123',
                'dropoff_address' => 'Quezon City',
                'dropoff_lat' => 14.6760,
                'dropoff_lng' => 121.0437,
                'cargo_kg' => 1500,
            ])
            ->assertRedirect();

        $booking = Booking::query()->first();
        $this->assertNotNull($booking);
        $this->assertSame('4500.00', (string) $booking->invoice?->amount);
    }

    public function test_customer_can_cancel_pending_booking_without_late_fee(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9200.00,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertRedirect(route('customer.bookings.index'));

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame('9200.00', (string) $booking->invoice?->amount);
    }

    public function test_customer_cannot_cancel_after_accept(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Accepted,
            'accepted_at' => now('Asia/Manila'),
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking))
            ->assertForbidden();
    }

    public function test_admin_update_cargo_keeps_single_fare_invoice(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pricing = Pricing::factory()->create([
            'amount' => 4500,
            'capacity_kg' => 1000,
        ]);
        $booking = Booking::factory()->create([
            'pricing_id' => $pricing->id,
            'cargo_kg' => 500,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 4500,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bookings.update', $booking), [
                'customer_id' => $booking->customer_id,
                'pricing_id' => $pricing->id,
                'booking_datetime' => $booking->booking_datetime->format('Y-m-d H:i:s'),
                'pickup_address' => $booking->pickup_address,
                'pickup_lat' => $booking->pickup_lat,
                'pickup_lng' => $booking->pickup_lng,
                'dropoff_address' => $booking->dropoff_address,
                'dropoff_lat' => $booking->dropoff_lat,
                'dropoff_lng' => $booking->dropoff_lng,
                'cargo_kg' => 2500,
            ])
            ->assertRedirect();

        $this->assertSame('4500.00', (string) $booking->fresh()->invoice?->amount);
    }
}
