<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_share_location_without_a_booking(): void
    {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->postJson(route('driver.location.store'), [
                'lat' => 14.55,
                'lng' => 121.02,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $driver->refresh();
        $this->assertEqualsWithDelta(14.55, (float) $driver->location_lat, 0.0001);
        $this->assertEqualsWithDelta(121.02, (float) $driver->location_lng, 0.0001);
        $this->assertNotNull($driver->location_updated_at);
    }

    public function test_customer_cannot_share_driver_location(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson(route('driver.location.store'), [
                'lat' => 14.55,
                'lng' => 121.02,
            ])
            ->assertRedirect(route('customer.home'));
    }

    public function test_customer_can_poll_assigned_driver_while_trip_is_active(): void
    {
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'location_lat' => 14.6,
            'location_lng' => 121.0,
            'location_updated_at' => now('Asia/Manila'),
        ]);
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
        ]);

        $this->actingAs($customer)
            ->getJson(route('customer.bookings.driver-location', $booking))
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('name', $driver->name);
    }

    public function test_customer_cannot_poll_driver_before_accept(): void
    {
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'location_lat' => 14.6,
            'location_lng' => 121.0,
        ]);
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($customer)
            ->getJson(route('customer.bookings.driver-location', $booking))
            ->assertForbidden();
    }

    public function test_customer_cannot_poll_driver_on_completed_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'location_lat' => 14.6,
            'location_lng' => 121.0,
        ]);
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($customer)
            ->getJson(route('customer.bookings.driver-location', $booking))
            ->assertForbidden();
    }

    public function test_admin_driver_map_includes_a_driver_without_a_booking(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create([
            'name' => 'Idle Driver',
            'location_lat' => 14.58,
            'location_lng' => 120.98,
            'location_updated_at' => now('Asia/Manila'),
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.drivers.map'))
            ->assertOk()
            ->assertSee('Driver map', false)
            ->assertSee('Search name, plate, or trip', false);

        $this->actingAs($admin)
            ->getJson(route('admin.drivers.locations'))
            ->assertOk()
            ->assertJsonPath('drivers.0.name', $driver->name)
            ->assertJsonPath('drivers.0.booking_number', null);
    }

    public function test_driver_map_locations_list_drivers_without_a_shared_location(): void
    {
        $staff = User::factory()->staff()->create();
        User::factory()->driver()->create([
            'name' => 'Located Driver',
            'location_lat' => 14.58,
            'location_lng' => 120.98,
        ]);
        User::factory()->driver()->create([
            'name' => 'Unlocated Driver',
            'location_lat' => null,
            'location_lng' => null,
        ]);

        $this->actingAs($staff)
            ->getJson(route('staff.drivers.locations'))
            ->assertOk()
            ->assertJsonCount(2, 'drivers')
            ->assertJsonPath('drivers.0.name', 'Located Driver')
            ->assertJsonPath('drivers.0.lat', 14.58)
            ->assertJsonPath('drivers.1.name', 'Unlocated Driver')
            ->assertJsonPath('drivers.1.lat', null)
            ->assertJsonPath('drivers.1.lng', null);
    }

    public function test_staff_can_open_driver_map_and_customer_cannot(): void
    {
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();

        $this->withoutVite()
            ->actingAs($staff)
            ->get(route('staff.drivers.map'))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('staff.drivers.map'))
            ->assertRedirect(route('customer.home'));
    }
}
