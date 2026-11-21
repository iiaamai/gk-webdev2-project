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

class DriverDeliveryListTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_deliveries_paginates_available_jobs(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '6-wheeler', 'amount' => 15000]);
        $driver = User::factory()->driver()->timedIn()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);

        for ($i = 0; $i < 13; $i++) {
            Booking::factory()->withGatepass()->create([
                'booking_number' => 'GK-TEST-AV'.$i,
                'pricing_id' => $pricing->id,
            ]);
        }

        $this->actingAs($driver)
            ->get(route('driver.deliveries.index'))
            ->assertOk()
            ->assertSee('Showing 1–12 of 13', false)
            ->assertSee('Page 1 of 2', false);
    }

    public function test_driver_deliveries_filters_by_search_and_status(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '6-wheeler', 'amount' => 15000]);
        $driver = User::factory()->driver()->timedIn()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);

        $target = Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-FIND',
            'pricing_id' => $pricing->id,
        ]);
        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-OTHER',
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.index', [
                'status' => BookingStatus::Pending->value,
                'q' => $target->booking_number,
            ]))
            ->assertOk()
            ->assertSee($target->booking_number)
            ->assertDontSee('GK-TEST-OTHER', false);
    }
}
