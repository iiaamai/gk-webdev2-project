<?php

namespace Tests\Feature;

use App\Models\Pricing;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_makati_and_quezon_city_sample_coords_are_inside_ncr(): void
    {
        $this->assertTrue(ServiceArea::contains(14.5547, 121.0244));
        $this->assertTrue(ServiceArea::contains(14.6760, 121.0437));
    }

    public function test_batangas_is_outside_ncr(): void
    {
        $this->assertFalse(ServiceArea::contains(13.7565, 121.0583));
    }

    public function test_customer_booking_rejects_pickup_outside_service_area(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '6-wheeler', 'amount' => 15000]);

        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->post(route('customer.bookings.store'), [
            'pricing_id' => $pricing->id,
            'booking_datetime' => now('Asia/Manila')->addDay()->toDateTimeString(),
            'pickup_address' => 'Outside NCR',
            'pickup_lat' => 13.7565,
            'pickup_lng' => 121.0583,
            'pickup_port_number' => 'P1',
            'pickup_container_number' => 'C1',
            'dropoff_address' => 'Quezon City, Metro Manila',
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
            'cargo_kg' => 1000,
        ]);

        $response->assertSessionHasErrors('pickup_lat');
    }
}
