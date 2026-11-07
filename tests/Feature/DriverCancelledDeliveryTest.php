<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\DriverTripCancelledNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverCancelledDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_driver_can_view_cancelled_trip_without_actions(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-CXL1',
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking))
            ->assertRedirect();

        $this->assertSame($driver->id, $booking->fresh()->driver_id);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $booking))
            ->assertOk()
            ->assertSee('This delivery was cancelled')
            ->assertSee('Back to dashboard')
            ->assertDontSee('Next step')
            ->assertDontSee('Navigate to pickup');

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::TransitToPickup->value,
            ])
            ->assertForbidden();
    }

    public function test_driver_home_shows_cancelled_flash_once(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-CXL2',
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking))
            ->assertRedirect();

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertOk()
            ->assertSee('GK-TEST-CXL2')
            ->assertSee('was cancelled')
            ->assertSee('Back to dashboard');

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertOk()
            ->assertDontSee('was cancelled');
    }

    public function test_cancelled_trip_does_not_block_accepting_another_job(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '6-wheeler', 'amount' => 15000]);
        $driver = User::factory()->driver()->timedIn()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);
        $cancelled = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Cancelled,
        ]);
        $open = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
        ]);

        $this->assertSame($driver->id, $cancelled->driver_id);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.accept', $open))
            ->assertRedirect(route('driver.deliveries.show', $open));
    }

    public function test_cancel_notice_is_stored_for_assigned_driver(): void
    {
        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->create([
            'booking_number' => 'GK-TEST-CXL3',
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
        ]);
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking));

        $this->assertSame('GK-TEST-CXL3', DriverTripCancelledNotice::peek($driver));
    }
}
