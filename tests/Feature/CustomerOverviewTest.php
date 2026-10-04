<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_overview_renders_snapshot_sections(): void
    {
        $customer = User::factory()->customer()->create();
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
        ]);
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.home'))
            ->assertOk()
            ->assertSee('Customer overview')
            ->assertSee('Pending')
            ->assertSee('In progress')
            ->assertSee('Active bookings')
            ->assertSee('New booking', false);
    }

    public function test_customer_bookings_index_uses_active_and_history_tabs(): void
    {
        $customer = User::factory()->customer()->create();
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_number' => 'GK-TEST-ACTIVE',
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_number' => 'GK-TEST-DONE',
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.index'))
            ->assertOk()
            ->assertSee('My Bookings')
            ->assertSee('role="tablist"', false)
            ->assertSee('tab-active', false)
            ->assertSee('tab-history', false)
            ->assertSee('Active')
            ->assertSee('History')
            ->assertSee('GK-TEST-ACTIVE')
            ->assertSee('GK-TEST-DONE')
            ->assertSee('View details', false);
    }

    public function test_customer_booking_show_is_polished(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Pending,
            'cargo_desc' => 'Office desks',
        ]);

        Http::fake();

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Trip details')
            ->assertSee('Pickup and dropoff for this booking.', false)
            ->assertDontSee('Shown after a driver accepts this booking.', false)
            ->assertSee('Waiting for gatepass and driver assignment')
            ->assertSee('Office desks')
            ->assertSee('Back to list', false);
    }

    public function test_customer_create_booking_form_is_polished(): void
    {
        config(['gk.mapbox_enabled' => false]);

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.bookings.create'))
            ->assertOk()
            ->assertSee('New booking')
            ->assertSee('Vehicle &amp; schedule', false)
            ->assertSee('Pickup')
            ->assertSee('Dropoff')
            ->assertSee('Port number', false)
            ->assertSee('Container number', false)
            ->assertSee('Pickup location map preview.', false)
            ->assertSee('Dropoff location map preview.', false)
            ->assertSee('Submit booking', false)
            ->assertDontSee('name="pickup_lat" type="number"', false);
    }

    public function test_customer_booking_show_displays_port_and_container(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'pickup_port_number' => 'PORT-99',
            'pickup_container_number' => 'CONT-XYZ',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Port number')
            ->assertSee('PORT-99')
            ->assertSee('Container number')
            ->assertSee('CONT-XYZ');
    }

    public function test_driver_cannot_open_customer_overview(): void
    {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->get(route('customer.home'))
            ->assertRedirect(route('driver.home'));
    }
}
