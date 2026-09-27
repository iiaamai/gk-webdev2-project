<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_receipt_pdf_for_any_status(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9200,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.bookings.receipt', $booking));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_admin_can_download_receipt_for_cancelled_booking(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Cancelled,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bookings.receipt', $booking))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_staff_can_download_receipt_only_when_completed(): void
    {
        $staff = User::factory()->staff()->create();
        $completed = Booking::factory()->create([
            'status' => BookingStatus::Completed,
        ]);
        $pending = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings.receipt', $completed))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($staff)
            ->get(route('staff.bookings.receipt', $pending))
            ->assertForbidden();
    }

    public function test_driver_can_download_receipt_only_when_completed(): void
    {
        $driver = User::factory()->driver()->create();
        $completed = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::Completed,
        ]);
        $inTransit = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::InTransit,
        ]);
        $cancelled = Booking::factory()->create([
            'driver_id' => $driver->id,
            'status' => BookingStatus::Cancelled,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.receipt', $completed))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($driver)
            ->get(route('driver.deliveries.receipt', $inTransit))
            ->assertForbidden();

        $this->actingAs($driver)
            ->get(route('driver.deliveries.receipt', $cancelled))
            ->assertForbidden();
    }
}
