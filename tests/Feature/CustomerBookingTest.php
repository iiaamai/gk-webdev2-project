<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_booking_with_invoice_amount_and_booking_number(): void
    {
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create([
            'vehicle_type' => '4-wheeler truck',
            'amount' => 9200.00,
        ]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);
        Setting::setValue('booking_seq', '42');

        $response = $this->actingAs($customer)->post(route('customer.bookings.store'), [
            'pricing_id' => $pricing->id,
            'booking_datetime' => now('Asia/Manila')->addDay()->format('Y-m-d H:i:s'),
            'pickup_address' => 'Makati City',
            'pickup_lat' => 14.5547,
            'pickup_lng' => 121.0244,
            'pickup_port_number' => 'PORT-01',
            'pickup_container_number' => 'CONT-ABC123',
            'dropoff_address' => 'Quezon City',
            'dropoff_lat' => 14.6760,
            'dropoff_lng' => 121.0437,
            'cargo_desc' => 'Test cargo',
        ]);

        $booking = Booking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking);
        $response->assertRedirect(route('customer.bookings.show', $booking));

        $year = now('Asia/Manila')->year;
        $this->assertSame("GK-{$year}-0042", $booking->booking_number);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame($pricing->id, $booking->pricing_id);
        $this->assertSame('PORT-01', $booking->pickup_port_number);
        $this->assertSame('CONT-ABC123', $booking->pickup_container_number);
        $this->assertSame('9200.00', (string) $booking->payout);
        $this->assertSame('9200.00', (string) $booking->invoice?->amount);
        $this->assertSame('43', Setting::getValue('booking_seq'));
    }

    public function test_customer_create_requires_port_and_container_numbers(): void
    {
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);

        $this->actingAs($customer)
            ->from(route('customer.bookings.create'))
            ->post(route('customer.bookings.store'), [
                'pricing_id' => $pricing->id,
                'booking_datetime' => now('Asia/Manila')->addDay()->format('Y-m-d H:i:s'),
                'pickup_address' => 'Makati City',
                'pickup_lat' => 14.5547,
                'pickup_lng' => 121.0244,
                'dropoff_address' => 'Quezon City',
                'dropoff_lat' => 14.6760,
                'dropoff_lng' => 121.0437,
            ])
            ->assertRedirect(route('customer.bookings.create'))
            ->assertSessionHasErrors(['pickup_port_number', 'pickup_container_number']);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_customer_cannot_create_booking_without_available_vehicle(): void
    {
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => 'L300 van', 'amount' => 4500]);
        Vehicle::factory()->maintenance()->create([
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($customer)
            ->from(route('customer.bookings.create'))
            ->post(route('customer.bookings.store'), [
                'pricing_id' => $pricing->id,
                'booking_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
                'pickup_address' => 'A',
                'pickup_lat' => 14.5,
                'pickup_lng' => 121.0,
                'pickup_port_number' => 'PORT-01',
                'pickup_container_number' => 'CONT-01',
                'dropoff_address' => 'B',
                'dropoff_lat' => 14.6,
                'dropoff_lng' => 121.1,
            ])
            ->assertRedirect(route('customer.bookings.create'))
            ->assertSessionHasErrors('pricing_id');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_customer_can_only_view_own_booking_details(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $other->id,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertForbidden();
    }

    public function test_customer_booking_index_lists_only_own_bookings(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        Booking::factory()->create(['customer_id' => $customer->id, 'booking_number' => 'GK-TEST-0001']);
        Booking::factory()->create(['customer_id' => $other->id, 'booking_number' => 'GK-TEST-0002']);

        $this->actingAs($customer)
            ->get(route('customer.bookings.index'))
            ->assertOk()
            ->assertSee('GK-TEST-0001')
            ->assertDontSee('GK-TEST-0002');
    }

    public function test_driver_cannot_access_customer_booking_routes(): void
    {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->get(route('customer.bookings.index'))
            ->assertRedirect(route('driver.home'));
    }
}
