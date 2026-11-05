<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_home_shows_to_pickup_and_on_trip_kpis(): void
    {
        $staff = User::factory()->staff()->create();

        Booking::factory()->create(['status' => BookingStatus::TransitToPickup]);
        Booking::factory()->create(['status' => BookingStatus::InTransit]);
        Booking::factory()->create(['status' => BookingStatus::ArrivedAtPickup]);

        $this->actingAs($staff)
            ->get(route('staff.home'))
            ->assertOk()
            ->assertSee('To pickup')
            ->assertSee('On trip')
            ->assertDontSee('In transit');
    }

    public function test_bookings_index_phase_filter_on_trip(): void
    {
        $staff = User::factory()->staff()->create();
        Booking::factory()->create([
            'booking_number' => 'GK-PHASE-ON',
            'status' => BookingStatus::TransitCompleted,
        ]);
        Booking::factory()->create([
            'booking_number' => 'GK-PHASE-PICKUP',
            'status' => BookingStatus::TransitToPickup,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings.index', ['phase' => 'on_trip']))
            ->assertOk()
            ->assertSee('GK-PHASE-ON')
            ->assertDontSee('GK-PHASE-PICKUP');
    }

    public function test_trip_snapshot_uses_booking_pricing_after_vehicle_type_change(): void
    {
        $staff = User::factory()->staff()->create();
        $original = Pricing::factory()->create(['vehicle_type' => '6-wheeler']);
        $updated = Pricing::factory()->create(['vehicle_type' => '10-wheeler']);
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create([
            'pricing_id' => $original->id,
            'driver_id' => $driver->id,
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::ArrivedAtPickup,
            'pricing_id' => $updated->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'vehicle_type_changed_at' => now(),
            'arrived_at_pickup_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertSee('10-wheeler')
            ->assertDontSee('6-wheeler');
    }

    public function test_customer_booking_detail_shows_status_callout_when_en_route(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::TransitToPickup,
            'driver_id' => User::factory()->driver()->create()->id,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('on the way to the pickup location', false);
    }
}
