<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffAdminBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_booking_edit_shows_dropoff_map_slot_without_driver(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create([
            'driver_id' => null,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('Drop-off location map preview', false);
    }

    public function test_admin_booking_show_mirrors_edit_top_with_map_slot_without_driver(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create([
            'driver_id' => null,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Trip snapshot')
            ->assertSee('Destination &amp; route', false)
            ->assertSee('Drop-off location map preview', false)
            ->assertDontSee('Trip details');
    }

    public function test_staff_can_update_booking_before_gatepass(): void
    {
        $staff = User::factory()->staff()->create();
        Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $booking = Booking::factory()->create([
            'pickup_address' => 'Old pickup',
        ]);

        $this->actingAs($staff)
            ->put(route('staff.bookings.update', $booking), $this->bookingPayload($booking, [
                'pickup_address' => 'New pickup',
            ]))
            ->assertRedirect(route('staff.bookings.edit', $booking));
    }

    public function test_staff_cannot_update_booking_after_gatepass(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->withGatepass()->create();

        $this->actingAs($staff)
            ->put(route('staff.bookings.update', $booking), $this->bookingPayload($booking, [
                'pickup_address' => 'Blocked',
            ]))
            ->assertForbidden();
    }

    public function test_staff_can_upload_gatepass_once(): void
    {
        Storage::fake('local');
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($staff)
            ->post(route('staff.bookings.gatepass.store', $booking), [
                'gatepass' => UploadedFile::fake()->image('gatepass.jpg'),
            ])
            ->assertRedirect(route('staff.bookings.edit', $booking));

        $booking->refresh();
        $this->assertTrue($booking->hasGatepass());
        Storage::disk('local')->assertExists($booking->gatepass_path);
    }

    public function test_staff_can_replace_gatepass(): void
    {
        Storage::fake('local');
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'gatepass_path' => 'bookings/GK-TEST-0001/gatepass.jpg',
        ]);
        Storage::disk('local')->put($booking->gatepass_path, 'original');

        $this->actingAs($staff)
            ->post(route('staff.bookings.gatepass.store', $booking), [
                'gatepass' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect(route('staff.bookings.edit', $booking));

        $booking->refresh();
        $this->assertTrue($booking->hasGatepass());
        Storage::disk('local')->assertExists($booking->gatepass_path);
    }

    public function test_staff_can_cancel_before_gatepass(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.cancel', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_staff_cannot_cancel_after_gatepass(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->withGatepass()->create(['status' => BookingStatus::Pending]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.cancel', $booking))
            ->assertForbidden();
    }

    public function test_admin_can_create_booking_for_customer(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 5000]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.bookings.store'), array_merge($this->bookingPayload(null, [
                'pricing_id' => $pricing->id,
            ]), [
                'customer_id' => $customer->id,
            ]));

        $booking = Booking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking);
        $response->assertRedirect(route('admin.bookings.edit', $booking));

        $this->assertSame(1, Booking::query()->where('customer_id', $customer->id)->count());
    }

    public function test_admin_cannot_assign_driver_with_mismatched_vehicle_type_on_create(): void
    {
        $truckPricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $sixPricing = Pricing::factory()->create(['vehicle_type' => '6-wheeler truck', 'amount' => 12000]);
        Vehicle::factory()->create([
            'pricing_id' => $truckPricing->id,
            'status' => VehicleStatus::Available,
        ]);
        $admin = User::factory()->systemAdmin()->create();
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $sixPricing->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.bookings.create'))
            ->post(route('admin.bookings.store'), array_merge($this->bookingPayload(null, [
                'pricing_id' => $truckPricing->id,
            ]), [
                'customer_id' => $customer->id,
                'driver_id' => $driver->id,
            ]))
            ->assertRedirect(route('admin.bookings.create'))
            ->assertSessionHasErrors('driver_id');
    }

    public function test_admin_can_keep_legacy_mismatched_driver_on_update(): void
    {
        $truckPricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $sixPricing = Pricing::factory()->create(['vehicle_type' => '6-wheeler truck', 'amount' => 12000]);
        Vehicle::factory()->create([
            'pricing_id' => $truckPricing->id,
            'status' => VehicleStatus::Available,
        ]);
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $sixPricing->id,
            'driver_id' => $driver->id,
        ]);
        $booking = Booking::factory()->create([
            'pricing_id' => $truckPricing->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bookings.update', $booking), array_merge($this->bookingPayload($booking, []), [
                'customer_id' => $booking->customer_id,
                'driver_id' => $driver->id,
                'pickup_address' => 'Updated pickup',
            ]))
            ->assertRedirect(route('admin.bookings.edit', $booking));

        $this->assertSame($driver->id, $booking->fresh()->driver_id);
        $this->assertSame('Updated pickup', $booking->fresh()->pickup_address);
    }

    public function test_admin_can_replace_gatepass(): void
    {
        Storage::fake('local');
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'gatepass_path' => 'bookings/GK-TEST-0001/gatepass.jpg',
        ]);
        Storage::disk('local')->put($booking->gatepass_path, 'original');

        $this->actingAs($admin)
            ->post(route('admin.bookings.gatepass.store', $booking), [
                'gatepass' => UploadedFile::fake()->image('replaced.png'),
            ])
            ->assertRedirect(route('admin.bookings.edit', $booking));
    }

    public function test_admin_can_archive_booking(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertRedirect(route('admin.bookings.index'));

        $archived = Booking::withArchived()->find($booking->id);
        $this->assertNotNull($archived);
        $this->assertTrue($archived->isArchived());
    }

    public function test_staff_can_download_gatepass_customer_cannot(): void
    {
        Storage::fake('local');
        $booking = Booking::factory()->withGatepass()->create([
            'gatepass_path' => 'bookings/GK-TEST-0001/gatepass.jpg',
        ]);
        Storage::disk('local')->put($booking->gatepass_path, 'image-bytes');

        $staff = User::factory()->staff()->create();
        $customer = $booking->customer;

        $this->actingAs($staff)
            ->get(route('documents.bookings.gatepass', $booking))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('documents.bookings.gatepass', $booking))
            ->assertForbidden();
    }

    public function test_cancel_releases_assigned_vehicle(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::InUse]);
        $booking = Booking::factory()->withGatepass()->create([
            'status' => BookingStatus::Accepted,
            'vehicle_id' => $vehicle->id,
            'driver_id' => User::factory()->driver()->create()->id,
            'is_locked' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking))
            ->assertRedirect();

        $booking->refresh();
        $vehicle->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertNull($booking->vehicle_id);
        $this->assertNull($booking->driver_id);
        $this->assertSame(VehicleStatus::Available, $vehicle->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingPayload(?Booking $booking, array $overrides): array
    {
        $pricingId = $overrides['pricing_id']
            ?? $booking?->pricing_id
            ?? Pricing::query()->where('vehicle_type', '4-wheeler truck')->value('id')
            ?? Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200])->id;

        return array_merge([
            'status' => $booking?->status->value ?? BookingStatus::Pending->value,
            'pricing_id' => $pricingId,
            'booking_datetime' => ($booking?->booking_datetime ?? now()->addDay())->format('Y-m-d H:i:s'),
            'pickup_address' => $booking?->pickup_address ?? 'Makati',
            'pickup_lat' => $booking?->pickup_lat ?? 14.5,
            'pickup_lng' => $booking?->pickup_lng ?? 121.0,
            'dropoff_address' => $booking?->dropoff_address ?? 'QC',
            'dropoff_lat' => $booking?->dropoff_lat ?? 14.6,
            'dropoff_lng' => $booking?->dropoff_lng ?? 121.1,
            'cargo_desc' => $booking?->cargo_desc,
        ], $overrides);
    }
}
