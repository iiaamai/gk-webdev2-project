<?php

namespace Tests\Feature;

use App\Actions\TimeInDriver;
use App\Enums\BookingStatus;
use App\Enums\MultiTripOfficeStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Support\BookingMultiTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTripPickupTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_trip_flow_updates_invoice_after_office_approve_and_driver_accept(): void
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create();
        app(TimeInDriver::class)->execute($driver, $staff);
        $pricing = Pricing::factory()->create(['amount' => 4500, 'capacity_kg' => 1000]);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'pricing_id' => $pricing->id,
            'status' => BookingStatus::TransitToPickup,
            'cargo_kg' => 1500,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 4500,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.arrived-at-pickup', $booking))
            ->assertRedirect();

        $booking->refresh();
        $this->assertNotNull($booking->arrived_at_pickup_at);
        $this->assertSame(BookingStatus::ArrivedAtPickup, $booking->status);

        $this->actingAs($customer)
            ->post(route('customer.bookings.multi-trip.request', $booking), ['round_trips' => 3])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame(MultiTripOfficeStatus::Pending, $booking->multi_trip_office_status);
        $this->assertSame(3, $booking->multi_trip_requested_rounds);

        $this->actingAs($staff)
            ->post(route('staff.bookings.multi-trip.approve', $booking), ['round_trips' => 3])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('13500.00', (string) $booking->invoice?->amount);
        $this->assertNotNull($booking->multi_trip_customer_confirmed_at);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.multi-trip.accept', $booking))
            ->assertRedirect();

        $booking->refresh();
        $this->assertNotNull($booking->multi_trip_driver_accepted_at);
        $this->assertFalse(BookingMultiTrip::multiTripBlocksCompletion($booking));
    }

    public function test_customer_must_confirm_when_office_changes_round_trip_count(): void
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create();
        app(TimeInDriver::class)->execute($driver, User::factory()->staff()->create());

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::ArrivedAtPickup,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => 2,
            'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.multi-trip.approve', $booking), ['round_trips' => 4])
            ->assertRedirect();

        $booking->refresh();
        $this->assertNull($booking->multi_trip_customer_confirmed_at);
        $this->assertTrue(BookingMultiTrip::needsCustomerConfirm($booking));

        $this->actingAs($customer)
            ->post(route('customer.bookings.multi-trip.confirm', $booking))
            ->assertRedirect();

        $this->assertNotNull($booking->fresh()->multi_trip_customer_confirmed_at);
    }

    public function test_driver_cannot_complete_while_multi_trip_pending(): void
    {
        $driver = User::factory()->driver()->create();
        app(TimeInDriver::class)->execute($driver, User::factory()->staff()->create());

        $booking = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::TransitCompleted,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => 2,
            'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
        ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), ['status' => 'completed'])
            ->assertSessionHasErrors('status');
    }

    public function test_wrong_truck_fee_preserved_when_office_approves_multi_trip(): void
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create();
        app(TimeInDriver::class)->execute($driver, $staff);

        $large = Pricing::factory()->create(['amount' => 9200]);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'pricing_id' => $large->id,
            'status' => BookingStatus::ArrivedAtPickup,
            'arrived_at_pickup_at' => now(),
            'vehicle_type_changed_at' => now(),
            'multi_trip_requested_rounds' => 2,
            'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
        ]);

        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9650,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.multi-trip.approve', $booking), ['round_trips' => 4])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('37250.00', (string) $booking->invoice?->amount);
    }
}
