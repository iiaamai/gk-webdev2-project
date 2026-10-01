<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bookings_index_filters_by_status_and_search(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $pending = Booking::factory()->create(['status' => BookingStatus::Pending]);
        $completed = Booking::factory()->create(['status' => BookingStatus::Completed]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index', [
                'status' => BookingStatus::Pending->value,
                'q' => $pending->booking_number,
            ]))
            ->assertOk()
            ->assertSee($pending->booking_number)
            ->assertDontSee($completed->booking_number, false);
    }

    public function test_staff_bookings_index_shows_no_matches_state(): void
    {
        $staff = User::factory()->staff()->create();
        Booking::factory()->create();

        $this->actingAs($staff)
            ->get(route('staff.bookings.index', ['q' => 'no-such-booking-xyz']))
            ->assertOk()
            ->assertSee('No matches', false);
    }
}
