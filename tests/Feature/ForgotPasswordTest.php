<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_forgot_password_page(): void
    {
        $this->get(route('password.forgot'))
            ->assertOk()
            ->assertSee('Forgot password');
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->from(route('password.forgot'))
            ->post(route('password.lookup'), [
                'email' => 'nobody@example.com',
            ])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors('email');
    }

    public function test_staff_email_cannot_reset_from_forgot_password(): void
    {
        User::factory()->staff()->create([
            'email' => 'staff.reset@example.com',
        ]);

        $this->from(route('password.forgot'))
            ->post(route('password.lookup'), [
                'email' => 'staff.reset@example.com',
            ])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors('email');
    }

    public function test_customer_can_lookup_email_then_set_a_new_password(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'reset.me@example.com',
            'password' => 'old-password',
        ]);

        $this->from(route('password.forgot'))
            ->post(route('password.lookup'), [
                'email' => 'reset.me@example.com',
            ])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHas('password_reset_email', 'reset.me@example.com');

        $this->post(route('password.update'), [
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('login'));

        $customer->refresh();
        $this->assertTrue(Hash::check('Password1!', $customer->password));
        $this->assertGuest();
    }

    public function test_weak_reset_password_is_rejected(): void
    {
        User::factory()->driver()->create([
            'email' => 'driver.reset@example.com',
        ]);

        $this->post(route('password.lookup'), [
            'email' => 'driver.reset@example.com',
        ]);

        $this->from(route('password.forgot'))
            ->post(route('password.update'), [
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors('password');
    }

    public function test_password_update_without_lookup_is_rejected(): void
    {
        $this->from(route('password.forgot'))
            ->post(route('password.update'), [
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors('email');
    }
}
