<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EirPodTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_upload_eir_and_pod_on_active_delivery(): void
    {
        Storage::fake('local');

        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ])
            ->assertRedirect(route('driver.deliveries.show', $booking));

        $this->actingAs($driver)
            ->post(route('driver.deliveries.pod.store', $booking), [
                'photos' => [
                    UploadedFile::fake()->image('pod-1.jpg'),
                    UploadedFile::fake()->image('pod-2.jpg'),
                ],
            ])
            ->assertRedirect(route('driver.deliveries.show', $booking));

        $booking->refresh();
        $this->assertNotNull($booking->eir);
        $this->assertNotNull($booking->pod);
        $this->assertCount(2, $booking->pod->photo_paths);
        Storage::disk('local')->assertExists($booking->eir->eir_path);
        Storage::disk('local')->assertExists($booking->pod->photo_paths[0]);
    }

    public function test_driver_cannot_upload_eir_when_not_assigned(): void
    {
        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'status' => BookingStatus::InTransit,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_driver_can_complete_after_eir_and_pod_uploaded(): void
    {
        Storage::fake('local');

        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::InUse]);
        $booking = Booking::factory()->withGatepass()->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.pod.store', $booking), [
                'photos' => [UploadedFile::fake()->image('pod.jpg')],
            ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertRedirect(route('driver.deliveries.show', $booking));

        $booking->refresh();
        $this->assertSame(BookingStatus::Completed, $booking->status);
    }

    public function test_customer_can_view_eir_when_in_transit_but_not_pod(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('documents.bookings.eir', $booking))
            ->assertNotFound();

        $this->actingAs($driver)
            ->post(route('driver.deliveries.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ]);

        Storage::disk('local')->put($booking->fresh()->eir->eir_path, 'bytes');

        $this->actingAs($customer)
            ->get(route('documents.bookings.eir', $booking))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('documents.bookings.pod.photo', [$booking, 0]))
            ->assertForbidden();
    }

    public function test_customer_can_view_pod_when_completed(): void
    {
        Storage::fake('local');

        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::InUse]);
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ]);
        $this->actingAs($driver)
            ->post(route('driver.deliveries.pod.store', $booking), [
                'photos' => [UploadedFile::fake()->image('pod.jpg')],
            ]);

        $this->actingAs($driver)
            ->patch(route('driver.deliveries.status.update', $booking), [
                'status' => BookingStatus::Completed->value,
            ]);

        $pod = $booking->fresh()->pod;
        Storage::disk('local')->put($pod->photo_paths[0], 'photo');

        $this->actingAs($customer)
            ->get(route('documents.bookings.pod.photo', [$booking, 0]))
            ->assertOk();
    }

    public function test_admin_can_upload_eir_for_booking(): void
    {
        Storage::fake('local');
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create(['status' => BookingStatus::Accepted]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.eir.store', $booking), [
                'eir' => UploadedFile::fake()->image('eir.jpg'),
            ])
            ->assertRedirect(route('admin.bookings.edit', $booking));

        $this->assertNotNull($booking->fresh()->eir);
    }

    public function test_admin_and_customer_booking_show_use_documents_panel(): void
    {
        Storage::fake('local');

        $admin = User::factory()->systemAdmin()->create();
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
            'gatepass_path' => 'bookings/GK-TEST-DOCS/gatepass.jpg',
        ]);
        Storage::disk('local')->put($booking->gatepass_path, 'gatepass');

        $this->actingAs($admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Documents')
            ->assertSee('documents/bookings/'.$booking->id.'/gatepass', false)
            ->assertDontSee('EIR: <a', false);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Documents')
            ->assertDontSee('documents/bookings/'.$booking->id.'/gatepass', false);
    }
}
