<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Rating;
use App\Models\User;
use App\Services\EarningsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RatingsActivityEarningsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_rate_completed_own_booking_once(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.rating.store', $booking), [
                'score' => 5,
                'comment' => 'Great trip',
            ])
            ->assertRedirect(route('customer.bookings.show', $booking));

        $this->assertDatabaseHas('ratings', [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'score' => 5,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.rating.store', $booking), [
                'score' => 4,
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_rate_incomplete_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::InTransit,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.rating.store', $booking), [
                'score' => 5,
            ])
            ->assertForbidden();
    }

    public function test_login_creates_activity_log_with_ip(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'rater@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'rater@example.com',
            'password' => 'password',
            'geo_lat' => '14.5995000',
            'geo_lng' => '120.9842000',
        ])->assertRedirect();

        $log = ActivityLog::query()->where('action', 'user.login')->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertNotNull($log->ip_address);
        $this->assertEqualsWithDelta(14.5995, (float) $log->geo_lat, 0.0001);
        $this->assertEqualsWithDelta(120.9842, (float) $log->geo_lng, 0.0001);
    }

    public function test_admin_activity_logs_filter_by_date_and_show_location(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        ActivityLog::factory()->create([
            'action' => 'booking.created',
            'description' => 'User logged in yesterday',
            'ip_location' => 'Manila, PH',
            'properties' => ['note' => 'seed'],
            'created_at' => now('Asia/Manila')->subDays(2),
        ]);
        ActivityLog::factory()->create([
            'action' => 'user.login',
            'description' => 'User logged in today',
            'created_at' => now('Asia/Manila'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', [
                'date_from' => now('Asia/Manila')->toDateString(),
                'date_to' => now('Asia/Manila')->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('User logged in today')
            ->assertSee('Showing 1 result', false)
            ->assertDontSee('User logged in yesterday');
    }

    public function test_ip_location_resolver_stores_location_when_enabled(): void
    {
        config(['gk.activity_ip_lookup' => true]);

        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'city' => 'Quezon City',
                'regionName' => 'Metro Manila',
                'country' => 'Philippines',
            ]),
        ]);

        $user = User::factory()->customer()->create([
            'email' => 'geo@example.com',
            'password' => 'password',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('login'), [
                'email' => 'geo@example.com',
                'password' => 'password',
            ])
            ->assertRedirect();

        $log = ActivityLog::query()->where('action', 'user.login')->first();
        $this->assertSame('Quezon City, Metro Manila, Philippines', $log?->ip_location);
    }

    public function test_admin_can_view_activity_logs_and_staff_cannot(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $staff = User::factory()->staff()->create();
        ActivityLog::factory()->create(['action' => 'booking.created']);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('booking.created');

        $this->actingAs($staff)
            ->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('staff.home'));
    }

    public function test_earnings_summary_sums_completed_payouts(): void
    {
        $first = Booking::factory()->create([
            'status' => BookingStatus::Completed,
            'accepted_at' => now('Asia/Manila')->startOfMonth(),
        ]);
        Invoice::factory()->create([
            'booking_id' => $first->id,
            'amount' => 1000,
        ]);

        $second = Booking::factory()->create([
            'status' => BookingStatus::Completed,
            'accepted_at' => now('Asia/Manila')->startOfMonth(),
        ]);
        Invoice::factory()->create([
            'booking_id' => $second->id,
            'amount' => 2500,
        ]);

        $pending = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);
        Invoice::factory()->create([
            'booking_id' => $pending->id,
            'amount' => 9999,
        ]);

        $summary = app(EarningsQuery::class)->summary();

        $this->assertSame(2, $summary['total_completed']);
        $this->assertSame('3500.00', $summary['total_payout']);
        $this->assertSame('1750.00', $summary['average_payout']);
        $this->assertTrue($summary['monthly']->isNotEmpty());
    }

    public function test_admin_earnings_page_renders(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Completed,
            'accepted_at' => now('Asia/Manila'),
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9200,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.earnings.index'))
            ->assertOk()
            ->assertSee('Earnings')
            ->assertSee('9,200.00')
            ->assertSee('earnings-payout-chart', false)
            ->assertSee('Completed revenue (invoiced)', false);
    }

    public function test_earnings_for_month_filters_completed_and_paid_revenue(): void
    {
        $now = now('Asia/Manila');
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Completed,
            'accepted_at' => $now->copy()->startOfMonth()->addDays(2),
        ]);
        Invoice::factory()->paid()->create([
            'booking_id' => $booking->id,
            'amount' => 5000,
            'paid_at' => $now->copy()->startOfMonth()->addDays(5),
        ]);

        $otherMonth = Booking::factory()->create([
            'status' => BookingStatus::Completed,
            'accepted_at' => $now->copy()->subMonth()->startOfMonth(),
        ]);
        Invoice::factory()->create([
            'booking_id' => $otherMonth->id,
            'amount' => 9999,
        ]);

        $report = app(EarningsQuery::class)->forMonth((int) $now->year, (int) $now->month);

        $this->assertSame(1, $report['kpis']['completed_trips']);
        $this->assertSame('5000.00', $report['kpis']['completed_revenue']);
        $this->assertSame('5000.00', $report['kpis']['paid_revenue']);
    }

    public function test_rating_creates_activity_log(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.rating.store', $booking), [
                'score' => 4,
            ]);

        $this->assertTrue(
            ActivityLog::query()
                ->where('action', 'rating.created')
                ->where('subject_id', $booking->id)
                ->exists()
        );
        $this->assertSame(1, Rating::query()->count());
    }
}
