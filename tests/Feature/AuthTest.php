<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\CompleteRegistrationMail;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_authenticated_user_visiting_login_is_redirected_to_role_home(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('login'))
            ->assertRedirect(route('staff.home'));

        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->get(route('login'))
            ->assertRedirect(route('driver.home'));
    }

    public function test_user_can_login_and_is_redirected_by_role(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('customer.home'));
        $this->assertAuthenticatedAs($customer);
    }

    public function test_driver_login_redirects_to_driver_home(): void
    {
        User::factory()->driver()->create([
            'email' => 'driver@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'driver@example.com',
            'password' => 'password',
        ])->assertRedirect(route('driver.home'));
    }

    public function test_staff_and_admin_login_redirect_to_their_portals(): void
    {
        User::factory()->staff()->create([
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'staff@example.com',
            'password' => 'password',
        ])->assertRedirect(route('staff.home'));

        $this->post(route('logout'));

        User::factory()->systemAdmin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.home'));
    }

    public function test_customer_can_register_and_is_auto_verified_while_mail_placeholder(): void
    {
        $response = $this->post(route('register.customer'), [
            'name' => 'New Customer',
            'email' => 'new.customer@example.com',
            'mobile' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('customer.home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'new.customer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_driver_can_register_without_vehicle_fields(): void
    {
        $response = $this->post(route('register.driver'), [
            'name' => 'New Driver',
            'email' => 'new.driver@example.com',
            'mobile' => '09171234568',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('driver.home'));

        $user = User::query()->where('email', 'new.driver@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Driver, $user->role);
        $this->assertNull($user->assignedVehicle);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_customer_stays_out_of_users_until_confirmation_link_when_mail_is_enabled(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $this->post(route('register.customer'), [
            'name' => 'Verify Customer',
            'email' => 'verify.customer@example.com',
            'mobile' => '09171234569',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('register.check'));

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'verify.customer@example.com')->first());
        $this->assertNotNull(PendingRegistration::query()->where('email', 'verify.customer@example.com')->first());
        Mail::assertSent(CompleteRegistrationMail::class);
    }

    public function test_confirmation_link_creates_verified_customer_and_logs_them_in(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $this->post(route('register.customer'), [
            'name' => 'Verify Customer',
            'email' => 'verify.customer@example.com',
            'mobile' => '09171234569',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $pending = PendingRegistration::query()->where('email', 'verify.customer@example.com')->first();
        $this->assertNotNull($pending);

        $this->get(URL::temporarySignedRoute(
            'register.confirm',
            $pending->expires_at,
            ['pendingRegistration' => $pending->id],
        ))->assertRedirect(route('customer.home'));

        $user = User::query()->where('email', 'verify.customer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertNull(PendingRegistration::query()->find($pending->id));
    }

    public function test_unverified_user_is_sent_to_verification_notice_instead_of_portal(): void
    {
        $customer = User::factory()->customer()->unverified()->create([
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect(route('customer.home'));

        $this->get(route('customer.home'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_role_middleware_blocks_cross_portal_access(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.home'))
            ->assertRedirect(route('customer.home'));
    }

    public function test_archived_user_cannot_login(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'archived@example.com',
            'password' => 'password',
        ]);
        $user->archive();

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'archived@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
