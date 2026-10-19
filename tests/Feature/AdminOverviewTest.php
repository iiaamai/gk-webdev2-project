<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_overview_renders_dashboard_sections(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        Booking::factory()->create([
            'status' => BookingStatus::InTransit,
            'accepted_at' => now('Asia/Manila'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.home'))
            ->assertOk()
            ->assertSee(asset('images/gk-logo-2.png'), false)
            ->assertSee('Admin overview')
            ->assertSee('Live operations map')
            ->assertSee('Earnings snapshot')
            ->assertSee('Recent activity')
            ->assertDontSee('Quick links', false);
    }

    public function test_admin_overview_map_booking_query_selects_active_trip(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $driver = User::factory()->driver()->create(['name' => 'Map Driver Ana']);
        $inTransit = Booking::factory()->create([
            'status' => BookingStatus::InTransit,
            'driver_id' => $driver->id,
        ]);
        $pending = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'gatepass_path' => 'bookings/test/gatepass.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.home', ['map_booking' => $pending->id]))
            ->assertOk()
            ->assertSee($pending->booking_number)
            ->assertSee($inTransit->booking_number)
            ->assertSeeInOrder(['Driver:', 'Unassigned'])
            ->assertSee('View booking', false)
            ->assertSee(route('admin.bookings.show', $pending), false);

        $this->actingAs($admin)
            ->get(route('admin.home', ['map_booking' => $inTransit->id]))
            ->assertOk()
            ->assertSeeInOrder(['Driver:', 'Map Driver Ana']);
    }
}
