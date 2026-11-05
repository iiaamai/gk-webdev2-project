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

class RoundTripLapProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_transit_completed_blocked_until_all_laps_marked(): void
    {
        [$driver, $booking] = $this->bookingInHaulWithApprovedRoundTrips(3);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertForbidden();

        $this->markLaps($driver, $booking, 2);
        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertForbidden();

        $this->markLaps($driver, $booking, 1);
        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertRedirect();

        $this->assertSame(BookingStatus::TransitCompleted, $booking->fresh()->status);
    }

    public function test_extra_lap_mark_blocked_at_n_of_n(): void
    {
        [$driver, $booking] = $this->bookingInHaulWithApprovedRoundTrips(2);

        $this->markLaps($driver, $booking, 2);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.round-trip-lap.complete', $booking))
            ->assertForbidden();
    }

    public function test_single_round_trip_skips_lap_ui_and_transit_completed(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $booking))
            ->assertOk()
            ->assertDontSee('Round trip progress');

        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertRedirect();
    }

    public function test_lap_progress_visible_to_all_roles(): void
    {
        [$driver, $booking] = $this->bookingInHaulWithApprovedRoundTrips(3);
        $customer = $booking->customer;
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->systemAdmin()->create();

        $this->markLaps($driver, $booking, 1);

        $this->actingAs($driver)->get(route('driver.deliveries.show', $booking))->assertSee('Round trip progress');
        $this->actingAs($customer)->get(route('customer.bookings.show', $booking))->assertSee('Round trip progress');
        $this->actingAs($staff)->get(route('staff.bookings.show', $booking))->assertSee('Round trip progress');
        $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertSee('Round trip progress');

        foreach ([$driver, $customer, $staff, $admin] as $viewer) {
            $this->actingAs($viewer)
                ->get($viewer->isDriver()
                    ? route('driver.deliveries.show', $booking)
                    : ($viewer->isCustomer()
                        ? route('customer.bookings.show', $booking)
                        : ($viewer->isStaff()
                            ? route('staff.bookings.show', $booking)
                            : route('admin.bookings.show', $booking))))
                ->assertSee('aria-valuenow="1"', false)
                ->assertSee('aria-valuemax="3"', false);
        }
    }

    public function test_customer_cannot_mark_lap(): void
    {
        [$driver, $booking] = $this->bookingInHaulWithApprovedRoundTrips(3);

        $this->assertFalse($booking->customer->can('markRoundTripLapComplete', $booking));
    }

    public function test_staff_cannot_mark_lap(): void
    {
        [$driver, $booking] = $this->bookingInHaulWithApprovedRoundTrips(3);
        $staff = User::factory()->staff()->create();

        $this->assertFalse($staff->can('markRoundTripLapComplete', $booking));
    }

    public function test_lap_banner_only_during_in_transit(): void
    {
        [$driver, $booking] = $this->bookingInHaulWithApprovedRoundTrips(3);
        $booking->update(['status' => BookingStatus::ArrivedAtPickup, 'arrived_at_pickup_at' => now()]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $booking))
            ->assertOk()
            ->assertDontSee('Round trip progress');
    }

    public function test_starting_haul_resets_lap_counter(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['amount' => 25000]);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'pricing_id' => $pricing->id,
            'status' => BookingStatus::ArrivedAtPickup,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => 2,
            'multi_trip_office_status' => MultiTripOfficeStatus::Approved,
            'multi_trip_office_rounds' => 2,
            'multi_trip_customer_confirmed_at' => now(),
            'multi_trip_driver_accepted_at' => now(),
            'multi_trip_laps_completed' => 99,
        ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), ['status' => 'in_transit'])
            ->assertRedirect();

        $this->assertSame(0, (int) $booking->fresh()->multi_trip_laps_completed);
    }

    /**
     * @return array{0: User, 1: Booking}
     */
    private function bookingInHaulWithApprovedRoundTrips(int $roundTrips): array
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create();
        app(TimeInDriver::class)->execute($driver, $staff);
        $pricing = Pricing::factory()->create(['amount' => 15000]);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'pricing_id' => $pricing->id,
            'status' => BookingStatus::InTransit,
            'arrived_at_pickup_at' => now(),
            'multi_trip_requested_rounds' => $roundTrips,
            'multi_trip_office_status' => MultiTripOfficeStatus::Approved,
            'multi_trip_office_rounds' => $roundTrips,
            'multi_trip_customer_confirmed_at' => now(),
            'multi_trip_driver_accepted_at' => now(),
            'multi_trip_laps_completed' => 0,
        ]);

        Invoice::factory()->create(['booking_id' => $booking->id, 'amount' => 15000 * $roundTrips]);

        $this->assertTrue(BookingMultiTrip::requiresLapTracking($booking));

        return [$driver, $booking];
    }

    private function markLaps(User $driver, Booking $booking, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->actingAs($driver)
                ->post(route('driver.deliveries.round-trip-lap.complete', $booking))
                ->assertRedirect();
            $booking->refresh();
        }
    }
}
