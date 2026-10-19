<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_not_found_page_uses_marketing_layout(): void
    {
        $this->withoutVite()
            ->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found', false)
            ->assertSee('GK Logistics', false);
    }

    public function test_admin_not_found_page_uses_admin_layout(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->withoutVite()
            ->actingAs($admin)
            ->get('/admin/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found', false)
            ->assertSee('GK Admin', false);
    }

    public function test_forbidden_page_uses_generic_copy(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'gatepass_path' => 'bookings/GK-TEST/gatepass.jpg',
        ]);

        $this->withoutVite()
            ->actingAs($customer)
            ->get(route('documents.bookings.gatepass', $booking))
            ->assertForbidden()
            ->assertSee('You cannot view this page', false);
    }
}
