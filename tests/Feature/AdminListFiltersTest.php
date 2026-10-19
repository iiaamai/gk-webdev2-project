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

    public function test_admin_bookings_index_shows_driver_column(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create(['name' => 'Admin Table Mira']);
        $assigned = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'driver_id' => $driver->id,
        ]);
        $unassigned = Booking::factory()->create(['status' => BookingStatus::Pending]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Driver')
            ->assertSee($assigned->booking_number)
            ->assertSee('Admin Table Mira')
            ->assertSee($unassigned->booking_number)
            ->assertSee('Unassigned');
    }

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

    public function test_admin_and_staff_booking_indexes_paginate_twelve_per_page(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $staff = User::factory()->staff()->create();
        Booking::factory()->count(13)->create();

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Showing 1–12 of 13', false)
            ->assertSee('Page 1 of 2', false)
            ->assertSee('Next', false);

        $this->actingAs($staff)
            ->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee('Showing 1–12 of 13', false)
            ->assertSee('Page 1 of 2', false);
    }
}
