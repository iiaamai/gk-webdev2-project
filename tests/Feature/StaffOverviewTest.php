<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StaffOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_overview_renders_ops_sections(): void
    {
        Http::fake();

        $staff = User::factory()->staff()->create();
        Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'gatepass_path' => null,
        ]);
        Booking::factory()->create([
            'status' => BookingStatus::InTransit,
            'accepted_at' => now('Asia/Manila'),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.home'))
            ->assertOk()
            ->assertSee('Staff overview')
            ->assertSee('Live operations map', false)
            ->assertSee('Needs gatepass')
            ->assertSee('Needs attention')
            ->assertSee('Active bookings')
            ->assertDontSee('Earnings snapshot', false)
            ->assertDontSee('Recent activity', false);
    }

    public function test_staff_bookings_index_uses_polished_table(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'gatepass_path' => null,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee('Bookings')
            ->assertSee($booking->booking_number)
            ->assertSee('View', false)
            ->assertSee(route('staff.bookings.show', $booking), false)
            ->assertSee(route('staff.bookings.edit', $booking), false);
    }

    public function test_staff_booking_show_offers_gatepass_upload_when_missing(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'gatepass_path' => null,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Upload gatepass', false)
            ->assertSee(route('staff.bookings.edit', $booking), false)
            ->assertSee('Edit workspace', false);
    }

    public function test_admin_cannot_open_staff_overview(): void
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->actingAs($admin)
            ->get(route('staff.home'))
            ->assertRedirect(route('admin.home'));
    }
}
