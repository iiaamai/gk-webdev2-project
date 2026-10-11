<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_must_verify_email_when_mail_is_enabled(): void
    {
        Notification::fake();
        config(['gk.mail_enabled' => true]);

        $this->post(route('register.customer'), [
            'name' => 'Verify Customer',
            'email' => 'verify.customer@example.com',
            'mobile' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('customer.home'));

        $user = User::query()->where('email', 'verify.customer@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)
            ->get(route('customer.home'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_driver_must_verify_email_when_mail_is_enabled(): void
    {
        Notification::fake();
        config(['gk.mail_enabled' => true]);

        $this->post(route('register.driver'), [
            'name' => 'Verify Driver',
            'email' => 'verify.driver@example.com',
            'mobile' => '09171234568',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('driver.home'));

        $user = User::query()->where('email', 'verify.driver@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_admin_created_staff_is_verified_when_mail_is_enabled(): void
    {
        Notification::fake();
        config(['gk.mail_enabled' => true]);

        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Office Staff',
            'email' => 'office.staff@example.com',
            'mobile' => '09170001111',
            'role' => UserRole::Staff->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('admin.users.index'));

        $staff = User::query()->where('email', 'office.staff@example.com')->first();

        $this->assertNotNull($staff);
        $this->assertNotNull($staff->email_verified_at);

        Notification::assertNothingSent();
    }
}
