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

    public function test_pending_booking_without_driver_still_builds_a_route(): void
    {
        config([
            'gk.mapbox_enabled' => true,
            'gk.mapbox_token' => 'pk.test-token',
        ]);

        Http::fake([
            'api.mapbox.com/directions/*' => Http::response([
                'routes' => [
                    [
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [121.0244, 14.5547],
                                [121.0437, 14.6760],
                            ],
                        ],
                        'distance' => 15340,
                        'duration' => 1620,
                    ],
                ],
            ]),
        ]);

        $booking = Booking::factory()->withGatepass()->create([
            'status' => BookingStatus::Pending,
            'driver_id' => null,
        ]);

        $result = app(BookingStaticRouteMapService::class)->forBooking($booking);

        $this->assertTrue($result->eligible);
        $this->assertTrue($result->hasRoute());
        Http::assertSentCount(1);
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
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [121.0244, 14.5547],
                                [121.0437, 14.6760],
                            ],
                        ],
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
        $this->assertTrue($result->hasRoute());
        $this->assertSame('LineString', $result->geometry['type']);
        $this->assertEqualsWithDelta(121.0244, $result->pickupLng, 0.00001);
        $this->assertEqualsWithDelta(14.6760, $result->dropoffLat, 0.00001);
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

    public function test_customer_booking_show_renders_zoom_limited_map_when_configured(): void
    {
        config([
            'gk.mapbox_enabled' => true,
            'gk.mapbox_token' => 'pk.test-token',
            'gk.mapbox_min_zoom' => 8,
            'gk.mapbox_max_zoom' => 17,
        ]);

        Http::fake([
            'api.mapbox.com/directions/*' => Http::response([
                'routes' => [
                    [
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [121.0244, 14.5547],
                                [121.0437, 14.6760],
                            ],
                        ],
                        'distance' => 15340,
                        'duration' => 1620,
                    ],
                ],
            ]),
        ]);

        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => User::factory()->driver()->create()->id,
            'status' => BookingStatus::Accepted,
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
            'is_locked' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertSee('data-route-map-for', false)
            ->assertSee('mapbox-gl.js', false)
            ->assertSee('Loading map', false)
            ->assertSee('"center":', false)
            ->assertSee('booking-route-map', false)
            ->assertSee('fill-primary', false)
            ->assertSee('fill-success', false)
            ->assertSee('minZoom', false)
            ->assertSee('maxZoom', false);
    }

    public function test_customer_create_includes_location_picker_when_mapbox_configured(): void
    {
        config([
            'gk.mapbox_enabled' => true,
            'gk.mapbox_token' => 'pk.test-token',
        ]);

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.bookings.create'))
            ->assertOk()
            ->assertSee('data-location-picker', false)
            ->assertSee('Set pickup', false)
            ->assertSee('Set dropoff', false)
            ->assertDontSee('Pickup location map preview.', false);
    }

    public function test_admin_edit_includes_location_picker_when_mapbox_configured(): void
    {
        config([
            'gk.mapbox_enabled' => true,
            'gk.mapbox_token' => 'pk.test-token',
        ]);

        Http::fake([
            'api.mapbox.com/directions/*' => Http::response([
                'routes' => [
                    [
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [121.0244, 14.5547],
                                [121.0437, 14.6760],
                            ],
                        ],
                        'distance' => 15340,
                        'duration' => 1620,
                    ],
                ],
            ]),
        ]);

        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create([
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('data-location-picker', false)
            ->assertSee('Set pickup', false)
            ->assertSee('Set dropoff', false)
            ->assertSee('booking-location-map', false)
            ->assertDontSee('data-route-map-for', false);
    }
}
