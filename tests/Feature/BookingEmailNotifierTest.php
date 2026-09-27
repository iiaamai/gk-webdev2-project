<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\VehicleStatus;
use App\Mail\BookingCreatedMail;
use App\Mail\BookingStatusChangedMail;
use App\Mail\GatepassUploadedMail;
use App\Mail\InvoiceIssuedMail;
use App\Mail\InvoicePaidMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingEmailNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_emails_are_sent_when_mail_is_disabled(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => false]);

        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 5000]);
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
            'dropoff_address' => 'B',
            'dropoff_lat' => 14.6,
            'dropoff_lng' => 121.1,
        ]);

        Mail::assertNothingSent();
    }

    public function test_booking_created_and_invoice_issued_emails_when_mail_enabled(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $customer = User::factory()->customer()->create();
        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 5000]);
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
            'dropoff_address' => 'B',
            'dropoff_lat' => 14.6,
            'dropoff_lng' => 121.1,
        ]);

        Mail::assertSent(BookingCreatedMail::class, fn (BookingCreatedMail $mail) => $mail->hasTo($customer->email));
        Mail::assertSent(InvoiceIssuedMail::class, fn (InvoiceIssuedMail $mail) => $mail->hasTo($customer->email));
    }

    public function test_gatepass_upload_notifies_matching_drivers(): void
    {
        Mail::fake();
        Storage::fake('local');
        config(['gk.mail_enabled' => true]);

        $truckPricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $vanPricing = Pricing::factory()->create(['vehicle_type' => 'L300 van', 'amount' => 4500]);
        $matching = User::factory()->driver()->create();
        $other = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'pricing_id' => $truckPricing->id,
            'driver_id' => $matching->id,
        ]);
        Vehicle::factory()->create([
            'pricing_id' => $vanPricing->id,
            'driver_id' => $other->id,
        ]);
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create([
            'pricing_id' => $truckPricing->id,
            'gatepass_path' => null,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.gatepass.store', $booking), [
                'gatepass' => UploadedFile::fake()->image('gatepass.jpg'),
            ]);

        Mail::assertSent(GatepassUploadedMail::class, fn (GatepassUploadedMail $mail) => $mail->hasTo($matching->email));
        Mail::assertNotSent(GatepassUploadedMail::class, fn (GatepassUploadedMail $mail) => $mail->hasTo($other->email));
    }

    public function test_accept_sends_accepted_email_to_customer(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $pricing = Pricing::factory()->create(['vehicle_type' => '4-wheeler truck', 'amount' => 9200]);
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        Vehicle::factory()->create([
            'plate_number' => 'ABC-1234',
            'pricing_id' => $pricing->id,
            'driver_id' => $driver->id,
            'status' => VehicleStatus::Available,
        ]);
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'pricing_id' => $pricing->id,
        ]);

        $this->actingAs($driver)->post(route('driver.deliveries.accept', $booking));

        Mail::assertSent(
            BookingStatusChangedMail::class,
            fn (BookingStatusChangedMail $mail) => $mail->hasTo($customer->email)
                && $mail->status === BookingStatus::Accepted,
        );
    }

    public function test_cancel_notifies_customer_and_assigned_driver(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        $admin = User::factory()->systemAdmin()->create();
        $booking = Booking::factory()->withGatepass()->create([
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Accepted,
            'is_locked' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.bookings.cancel', $booking));

        Mail::assertSent(
            BookingStatusChangedMail::class,
            fn (BookingStatusChangedMail $mail) => $mail->hasTo($customer->email)
                && $mail->status === BookingStatus::Cancelled,
        );
        Mail::assertSent(
            BookingStatusChangedMail::class,
            fn (BookingStatusChangedMail $mail) => $mail->hasTo($driver->email)
                && $mail->status === BookingStatus::Cancelled,
        );
    }

    public function test_mark_invoice_paid_sends_email_when_enabled(): void
    {
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($staff)->post(route('staff.bookings.invoice.mark-paid', $booking));

        Mail::assertSent(InvoicePaidMail::class, fn (InvoicePaidMail $mail) => $mail->hasTo($customer->email));
    }
}
