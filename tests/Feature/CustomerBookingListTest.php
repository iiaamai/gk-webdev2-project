<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingListTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_bookings_index_paginates_history_tab(): void
    {
        $customer = User::factory()->customer()->create();

        for ($i = 0; $i < 13; $i++) {
            Booking::factory()->create([
                'customer_id' => $customer->id,
                'status' => BookingStatus::Completed,
                'booking_number' => 'GK-TEST-H'.$i,
            ]);
        }

        $this->actingAs($customer)
            ->get(route('customer.bookings.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('Showing 1–12 of 13', false)
            ->assertSee('Page 1 of 2', false)
            ->assertSee('Next', false);
    }

    public function test_customer_bookings_index_separates_tabs_by_url(): void
    {
        $customer = User::factory()->customer()->create();
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_number' => 'GK-TEST-ACTIVE',
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_number' => 'GK-TEST-DONE',
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.index', ['tab' => 'active']))
            ->assertOk()
            ->assertSee('GK-TEST-ACTIVE')
            ->assertDontSee('GK-TEST-DONE', false);

        $this->actingAs($customer)
            ->get(route('customer.bookings.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('GK-TEST-DONE')
            ->assertDontSee('GK-TEST-ACTIVE', false);
    }

    public function test_customer_bookings_history_filters_by_payment_status(): void
    {
        $customer = User::factory()->customer()->create();
        $paid = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
            'booking_number' => 'GK-TEST-PAID',
        ]);
        Invoice::factory()->create([
            'booking_id' => $paid->id,
            'status' => InvoiceStatus::Paid,
        ]);
        $unpaid = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Completed,
            'booking_number' => 'GK-TEST-UNPAID',
        ]);
        Invoice::factory()->create([
            'booking_id' => $unpaid->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.index', [
                'tab' => 'history',
                'payment' => InvoiceStatus::Unpaid->value,
            ]))
            ->assertOk()
            ->assertSee('GK-TEST-UNPAID')
            ->assertDontSee('GK-TEST-PAID', false);
    }

    public function test_customer_bookings_index_filters_by_status_and_search(): void
    {
        $customer = User::factory()->customer()->create();
        $pending = Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_number' => 'GK-TEST-PEND-1',
            'status' => BookingStatus::Pending,
        ]);
        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_number' => 'GK-TEST-ACC-1',
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.index', [
                'tab' => 'active',
                'status' => BookingStatus::Pending->value,
                'q' => $pending->booking_number,
            ]))
            ->assertOk()
            ->assertSee($pending->booking_number)
            ->assertDontSee('GK-TEST-ACC-1', false);
    }
}
