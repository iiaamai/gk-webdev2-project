<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creation_creates_unpaid_invoice_with_payout_amount(): void
    {
        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 7500]);
        Vehicle::factory()->create([
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ]);

        $this->actingAs($customer)->post(route('customer.bookings.store'), [
            'pricing_id' => $pricing->id,
            'booking_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
            'pickup_address' => 'A',
            'pickup_lat' => 14.5,
            'pickup_lng' => 121.0,
            'pickup_port_number' => 'PORT-01',
            'pickup_container_number' => 'CONT-01',
            'dropoff_address' => 'B',
            'dropoff_lat' => 14.6,
            'dropoff_lng' => 121.1,
        ]);

        $booking = Booking::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($booking);

        $invoice = Invoice::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertSame('7500.00', (string) $invoice->amount);
        $this->assertNotNull($invoice->issued_at);
    }

    public function test_staff_can_mark_invoice_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();
        $invoice = Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => $booking->pricing?->amount ?? 9200,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.invoice.mark-paid', $booking), [
                'notes' => 'Cash received at office',
            ])
            ->assertRedirect(route('staff.bookings.show', $booking));

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame('Cash received at office', $invoice->notes);
    }

    public function test_admin_can_mark_invoice_paid(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();
        Invoice::factory()->create(['booking_id' => $booking->id, 'status' => InvoiceStatus::Unpaid]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.invoice.mark-paid', $booking))
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame(InvoiceStatus::Paid, $booking->fresh()->invoice->status);
    }

    public function test_customer_cannot_mark_invoice_paid(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create(['booking_id' => $booking->id, 'status' => InvoiceStatus::Unpaid]);

        $this->actingAs($customer)
            ->post(route('staff.bookings.invoice.mark-paid', $booking))
            ->assertRedirect(route('customer.home'));
    }

    public function test_cannot_mark_already_paid_invoice_again(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();
        Invoice::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->actingAs($staff)
            ->from(route('staff.bookings.show', $booking))
            ->post(route('staff.bookings.invoice.mark-paid', $booking))
            ->assertForbidden();
    }

    public function test_admin_can_revert_invoice_to_unpaid(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();
        $invoice = Invoice::factory()->paid()->create([
            'booking_id' => $booking->id,
            'notes' => 'Paid in error',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.invoice.mark-unpaid', $booking), [
                'notes' => 'Correcting mistaken payment',
            ])
            ->assertRedirect(route('admin.bookings.edit', $booking));

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertNull($invoice->paid_at);
        $this->assertSame('Correcting mistaken payment', $invoice->notes);
    }

    public function test_staff_cannot_revert_invoice_to_unpaid(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();
        Invoice::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->actingAs($staff)
            ->post(route('admin.bookings.invoice.mark-unpaid', $booking))
            ->assertRedirect(route('staff.home'));
    }

    public function test_cannot_revert_already_unpaid_invoice(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->create();
        Invoice::factory()->create(['booking_id' => $booking->id, 'status' => InvoiceStatus::Unpaid]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.invoice.mark-unpaid', $booking))
            ->assertForbidden();
    }

    public function test_customer_booking_show_includes_invoice_details(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 9200,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Invoice')
            ->assertSee('Unpaid')
            ->assertSee('Not paid yet', false);
    }
}
