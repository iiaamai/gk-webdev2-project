<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingLifecycleStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_driver_status_path_through_haul_and_transit_completed(): void
    {
        $driver = User::factory()->driver()->timedIn()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::TransitToPickup->value,
            ])
            ->assertRedirect();

        $this->assertSame(BookingStatus::TransitToPickup, $booking->fresh()->status);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.arrived-at-pickup', $booking))
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::ArrivedAtPickup, $booking->status);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::InTransit->value,
            ])
            ->assertRedirect();

        $this->assertSame(BookingStatus::InTransit, $booking->fresh()->status);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.transit-completed', $booking))
            ->assertRedirect();

        $this->assertSame(BookingStatus::TransitCompleted, $booking->fresh()->status);
    }
}
