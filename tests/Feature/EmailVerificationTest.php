<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\CompleteRegistrationMail;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_must_confirm_email_before_user_is_created_when_mail_is_enabled(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $this->post(route('register.customer'), [
            'name' => 'Verify Customer',
            'email' => 'verify.customer@example.com',
            'mobile' => '09171234567',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('register.check'));

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'verify.customer@example.com')->first());
        $this->assertNotNull(PendingRegistration::query()->where('email', 'verify.customer@example.com')->first());
        Mail::assertSent(CompleteRegistrationMail::class);
    }

    public function test_driver_must_confirm_email_before_user_is_created_when_mail_is_enabled(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $this->post(route('register.driver'), [
            'name' => 'Verify Driver',
            'email' => 'verify.driver@example.com',
            'mobile' => '09171234568',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('register.check'));

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'verify.driver@example.com')->first());
        Mail::assertSent(CompleteRegistrationMail::class);
    }

    public function test_login_tells_pending_signup_to_finish_from_email(): void
    {
        config(['gk.mail_enabled' => true]);

        PendingRegistration::factory()->create([
            'email' => 'pending@example.com',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'pending@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Check your email to finish creating your account.']);
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
