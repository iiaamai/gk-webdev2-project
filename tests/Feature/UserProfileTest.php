<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_update_profile_mobile_and_avatar(): void
    {
        Storage::fake('local');
        $driver = User::factory()->driver()->create([
            'name' => 'Old Name',
            'mobile' => '09171111111',
        ]);

        $this->actingAs($driver)
            ->put(route('profile.update'), [
                'name' => 'New Driver',
                'mobile' => '09172222222',
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect(route('driver.settings.edit'));

        $driver->refresh();
        $this->assertSame('New Driver', $driver->name);
        $this->assertSame('09172222222', $driver->mobile);
        $this->assertNotNull($driver->avatar_path);
        Storage::disk('local')->assertExists($driver->avatar_path);
    }

    public function test_customer_sees_driver_contact_after_accept(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'name' => 'Driver Contact',
            'mobile' => '09173333333',
        ]);
        $driver->forceFill(['avatar_path' => 'users/'.$driver->id.'/avatar.jpg'])->save();
        Storage::disk('local')->put($driver->avatar_path, 'fake-avatar');

        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Your driver', false)
            ->assertSee('Driver Contact')
            ->assertSee('09173333333');
    }

    public function test_customer_does_not_see_driver_contact_before_accept(): void
    {
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'name' => 'Hidden Driver',
            'mobile' => '09174444444',
        ]);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'driver_id' => null,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('Your driver', false)
            ->assertDontSee('Hidden Driver');
    }

    public function test_customer_can_view_assigned_driver_avatar_after_accept(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'avatar_path' => 'users/99/avatar.jpg',
        ]);
        Storage::disk('local')->put($driver->avatar_path, 'fake-avatar');

        Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
            'is_locked' => true,
        ]);

        $response = $this->actingAs($customer)
            ->get(route('users.avatar', $driver))
            ->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('inline', $disposition);
    }

    public function test_unrelated_customer_cannot_view_driver_avatar(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create([
            'avatar_path' => 'users/99/avatar.jpg',
        ]);
        Storage::disk('local')->put($driver->avatar_path, 'fake-avatar');

        $this->actingAs($customer)
            ->get(route('users.avatar', $driver))
            ->assertForbidden();
    }
}
