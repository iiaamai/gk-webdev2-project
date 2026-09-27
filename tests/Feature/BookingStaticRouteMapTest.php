<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingStaticRouteMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingStaticRouteMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_not_eligible_before_driver_accepts(): void
    {
        Http::fake();

        $booking = Booking::factory()->withGatepass()->create([
            'status' => BookingStatus::Pending,
            'driver_id' => null,
        ]);

        $result = app(BookingStaticRouteMapService::class)->forBooking($booking);

        $this->assertFalse($result->eligible);
        $this->assertFalse($result->hasImage());
        Http::assertNothingSent();
    }

    public function test_route_returns_not_configured_when_mapbox_disabled(): void
    {
        Http::fake();
        config(['gk.mapbox_enabled' => false]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::Accepted,
            'driver_id' => User::factory()->driver()->create()->id,
            'is_locked' => true,
        ]);

        $result = app(BookingStaticRouteMapService::class)->forBooking($booking);

        $this->assertTrue($result->eligible);
        $this->assertFalse($result->configured);
        $this->assertNull($result->message);
        Http::assertNothingSent();
    }

    public function test_route_builds_static_map_when_mapbox_configured(): void
    {
        config([
            'gk.mapbox_enabled' => true,
            'gk.mapbox_token' => 'pk.test-token',
        ]);

        Http::fake([
            'api.mapbox.com/directions/*' => Http::response([
                'routes' => [
                    [
                        'geometry' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
                        'distance' => 15340,
                        'duration' => 1620,
                    ],
                ],
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::InTransit,
            'driver_id' => User::factory()->driver()->create()->id,
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
            'is_locked' => true,
        ]);

        $result = app(BookingStaticRouteMapService::class)->forBooking($booking);

        $this->assertTrue($result->configured);
        $this->assertTrue($result->hasImage());
        $this->assertStringContainsString('api.mapbox.com/styles/v1/', $result->imageUrl);
        $this->assertSame(15.3, $result->distanceKm);
        $this->assertSame(27, $result->durationMinutes);
        Http::assertSentCount(1);
    }

    public function test_customer_booking_show_includes_route_section_after_accept(): void
    {
        Http::fake();
        config(['gk.mapbox_enabled' => false]);

        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => User::factory()->driver()->create()->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Pickup and dropoff for this booking.', false)
            ->assertSee('Pickup to dropoff route preview.', false);
    }
}
