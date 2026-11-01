<?php

namespace Tests\Feature;

use App\Actions\TimeInDriver;
use App\Actions\TimeOutDriver;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Support\DriverAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_login_allowed_after_staff_times_them_in(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create([
            'email' => 'timed.driver@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.drivers.time-in', $driver))
            ->assertRedirect();

        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'timed.driver@example.com',
            'password' => 'password',
        ])->assertRedirect(route('driver.home'));

        $this->assertAuthenticatedAs($driver);
    }

    public function test_driver_login_denied_after_time_out(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->timedIn($staff)->create([
            'email' => 'out.driver@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.drivers.time-out', $driver))
            ->assertRedirect();

        $this->post(route('logout'));

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'out.driver@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_driver_can_log_in_again_after_second_time_in_same_day(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->timedIn($staff)->create([
            'email' => 'cycle.driver@example.com',
            'password' => 'password',
        ]);

        app(TimeOutDriver::class)->execute($driver, $staff);
        app(TimeInDriver::class)->execute($driver, $staff);

        $this->post(route('login'), [
            'email' => 'cycle.driver@example.com',
            'password' => 'password',
        ])->assertRedirect(route('driver.home'));
    }

    public function test_time_out_blocked_when_driver_has_active_trip(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->timedIn($staff)->create();
        Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
        ]);

        $this->actingAs($staff)
            ->from(route('staff.drivers.index'))
            ->post(route('staff.drivers.time-out', $driver))
            ->assertRedirect(route('staff.drivers.index'))
            ->assertSessionHasErrors('attendance');

        $this->assertTrue(DriverAttendance::isTimedIn($driver->fresh()));
    }

    public function test_time_out_ends_existing_driver_session_on_next_request(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->timedIn($staff)->create();

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('staff.drivers.time-out', $driver))
            ->assertRedirect();

        $this->actingAs($driver)
            ->get(route('driver.home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_access_drivers_index_and_time_in(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create();

        $this->actingAs($admin)
            ->get(route('admin.drivers.index'))
            ->assertOk()
            ->assertSee($driver->email);

        $this->actingAs($admin)
            ->post(route('admin.drivers.time-in', $driver))
            ->assertRedirect();

        $this->assertTrue(DriverAttendance::isTimedIn($driver->fresh()));
    }

    public function test_customer_login_unaffected_by_attendance(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect(route('customer.home'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_admin_can_time_in_driver_from_user_show_route(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.time-in', $driver))
            ->assertRedirect();

        $this->assertTrue(DriverAttendance::isTimedIn($driver->fresh()));
    }
}
