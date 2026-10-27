<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Eir;
use App\Models\Pod;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_sees_only_available_jobs_with_gatepass_and_matching_type(): void
    {
        $truckPricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $vanPricing = Pricing::factory()->create(['vehicle_type' => 'L300 van', 'amount' => 4500]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $truckPricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);

        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-1001',
            'pricing_id' => $truckPricing->id,
        ]);
        Booking::factory()->create([
            'booking_number' => 'GK-TEST-1002',
            'pricing_id' => $truckPricing->id,
            'gatepass_path' => null,
        ]);
        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-1003',
            'pricing_id' => $vanPricing->id,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.index'))
            ->assertOk()
            ->assertSee('GK-TEST-1001')
            ->assertDontSee('GK-TEST-1002')
            ->assertDontSee('GK-TEST-1003');
    }

    public function test_driver_can_accept_job_and_locks_vehicle(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'ABC-1234',
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);
        $booking = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.accept', $booking))
            ->assertRedirect(route('driver.deliveries.show', $booking));

        $booking->refresh();
        $vehicle->refresh();

        $this->assertSame($driver->id, $booking->driver_id);
        $this->assertSame($vehicle->id, $booking->vehicle_id);
        $this->assertTrue($booking->is_locked);
        $this->assertSame(BookingStatus::Accepted, $booking->status);
        $this->assertSame(VehicleStatus::InUse, $vehicle->status);
        $this->assertNotNull($booking->accepted_at);
    }

    public function test_driver_cannot_accept_second_job_while_active(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);

        $active = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $another = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.accept', $another))
            ->assertForbidden();

        $this->assertNull($another->fresh()->driver_id);
        $this->assertSame($driver->id, $active->fresh()->driver_id);
    }

    public function test_driver_can_mark_delivery_in_transit(): void
    {
        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::InTransit->value,
            ])
            ->assertRedirect(route('driver.deliveries.show', $booking));

        $this->assertSame(BookingStatus::InTransit, $booking->fresh()->status);
    }

    public function test_driver_cannot_complete_without_eir_and_pod(): void
    {
        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->from(route('driver.deliveries.show', $booking))
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertRedirect(route('driver.deliveries.show', $booking))
            ->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::InTransit, $booking->fresh()->status);
    }

    public function test_driver_can_complete_when_eir_and_pod_exist(): void
    {
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::InUse]);
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);
        Eir::factory()->create(['booking_id' => $booking->id]);
        Pod::factory()->create(['booking_id' => $booking->id]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertRedirect(route('driver.deliveries.show', $booking));

        $booking->refresh();
        $vehicle->refresh();

        $this->assertSame(BookingStatus::Completed, $booking->status);
        $this->assertNull($booking->vehicle_id);
        $this->assertFalse($booking->is_locked);
        $this->assertSame($driver->id, $booking->driver_id);
        $this->assertSame(VehicleStatus::Available, $vehicle->status);
    }

    public function test_customer_cannot_access_driver_deliveries(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('driver.deliveries.index'))
            ->assertRedirect(route('customer.home'));
    }

    public function test_deliveries_index_renders_job_cards(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);

        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-CARD',
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.index'))
            ->assertOk()
            ->assertSee('Active delivery')
            ->assertSee('Available jobs')
            ->assertSee('GK-TEST-CARD')
            ->assertSee('Accept', false)
            ->assertSee('Navigate to pickup')
            ->assertSee('Navigate to dropoff')
            ->assertSee('nav=pickup')
            ->assertSee('nav=dropoff')
            ->assertDontSee('Finish your current delivery to accept new jobs', false);
    }

    public function test_delivery_show_renders_documents_and_route_cards(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::InUse,
        ]);
        $booking = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $booking))
            ->assertOk()
            ->assertSee('Documents', false)
            ->assertSee('Gatepass', false)
            ->assertSee('EIR', false)
            ->assertSee('POD', false)
            ->assertSee('Save EIR', false)
            ->assertSee('Save POD', false)
            ->assertSee("x-data='{ open: \"eir\" }'", false)
            ->assertSee('Destination &amp; route', false)
            ->assertSee('Navigate to pickup')
            ->assertSee('Navigate to dropoff')
            ->assertSee('Opens a full-screen map', false)
            ->assertSee('they are not emailed on each reroute', false)
            ->assertSee('Update status', false)
            ->assertDontSee('Recenter');
    }

    public function test_delivery_show_opens_fullscreen_navigation_for_nav_query(): void
    {
        config([
            'gk.mapbox_enabled' => true,
            'gk.mapbox_token' => 'pk.test-token',
        ]);

        Http::preventStrayRequests();
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

        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::InUse,
        ]);
        $booking = Booking::factory()->withGatepass()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', [$booking, 'nav' => 'pickup']))
            ->assertOk()
            ->assertSee('Exit')
            ->assertSee('Recenter')
            ->assertSee('"liveNav":true', false)
            ->assertSee('"fullscreen":true', false)
            ->assertSee('"navDestination":"pickup"', false)
            ->assertDontSee('Delivery workspace')
            ->assertDontSee('Documents', false);

        Http::assertSentCount(1);
    }

    public function test_document_routes_stream_inline_for_preview(): void
    {
        Storage::fake('local');
        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-INLINE',
            'gatepass_path' => 'bookings/GK-TEST-INLINE/gatepass.jpg',
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        Storage::disk('local')->put($booking->gatepass_path, 'fake-image-bytes');

        $response = $this->actingAs($driver)
            ->get(route('documents.bookings.gatepass', $booking))
            ->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('GK-TEST-INLINE-gatepass', $disposition);
    }

    public function test_available_jobs_are_view_only_when_driver_has_active_delivery(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::InUse,
        ]);

        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-ACTIVE',
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-WAITING',
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.index'))
            ->assertOk()
            ->assertSee('GK-TEST-ACTIVE')
            ->assertSee('GK-TEST-WAITING')
            ->assertSee('Finish your current delivery to accept new jobs', false)
            ->assertSee('View only', false)
            ->assertSee('View', false);
    }

    public function test_available_jobs_are_ordered_oldest_first(): void
    {
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);

        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-LATER',
            'pricing_id' => $pricing->id,
            'created_at' => now('Asia/Manila')->subHour(),
        ]);
        Booking::factory()->withGatepass()->create([
            'booking_number' => 'GK-TEST-EARLIER',
            'pricing_id' => $pricing->id,
            'created_at' => now('Asia/Manila')->subHours(2),
        ]);

        $response = $this->actingAs($driver)
            ->get(route('driver.deliveries.index'))
            ->assertOk()
            ->assertSee('Accept', false);

        $this->assertTrue(
            strpos($response->getContent(), 'GK-TEST-EARLIER') < strpos($response->getContent(), 'GK-TEST-LATER'),
        );
    }
}
