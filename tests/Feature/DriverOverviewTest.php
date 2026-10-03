<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_overview_renders_snapshot_sections(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'plate_number' => 'ABC-1234',
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);

        Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertOk()
            ->assertSee('Driver overview')
            ->assertSee('Available jobs')
            ->assertSee('ABC-1234')
            ->assertSee('4-wheeler truck')
            ->assertSee('Jobs waiting');
    }

    public function test_driver_settings_page_renders(): void
    {
        $driver = User::factory()->driver()->create(['name' => 'Demo Driver']);

        $this->actingAs($driver)
            ->get(route('driver.settings.edit'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Demo Driver');
    }

    public function test_customer_cannot_open_driver_overview(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('driver.home'))
            ->assertRedirect(route('customer.home'));
    }

    public function test_driver_overview_shows_active_delivery_summary(): void
    {
        $driver = User::factory()->driver()->create();
        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-ACTIVE',
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertOk()
            ->assertSee('GK-TEST-ACTIVE')
            ->assertSee('Open', false)
            ->assertSee('in_transit', false);
    }

    public function test_driver_overview_view_only_copy_when_active_and_jobs_waiting(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::InUse,
        ]);

        Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertOk()
            ->assertSee('Jobs waiting')
            ->assertSee('Finish your current delivery to accept new jobs', false);
    }
}
