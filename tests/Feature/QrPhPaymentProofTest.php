<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProofStatus;
use App\Mail\PaymentProofRejectedMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrPhPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_company_qr_and_customer_can_view_it(): void
    {
        Storage::fake('local');

        $admin = User::factory()->systemAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'company_name' => 'GK Trucking Services',
                'support_email' => 'help@gk.test',
                'booking_seq' => 10,
                'map_center_lat' => 14.6,
                'map_center_lng' => 120.98,
                'map_zoom' => 12,
                'qr_ph' => UploadedFile::fake()->image('qr.png'),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('settings/qr-ph.png', Setting::getValue('qr_ph_path'));
        Storage::disk('local')->assertExists('settings/qr-ph.png');

        $this->actingAs($customer)
            ->get(route('documents.qr-ph'))
            ->assertOk();

        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Pay via QR Ph (InstaPay)', false)
            ->assertSee('View larger QR', false)
            ->assertSee('Enlarged GK Trucking QR Ph', false)
            ->assertDontSee('>Close<', false);
    }

    public function test_staff_cannot_update_company_qr(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->put(route('admin.settings.update'), [
                'company_name' => 'GK Trucking Services',
                'support_email' => 'help@gk.test',
                'booking_seq' => 10,
                'map_center_lat' => 14.6,
                'map_center_lng' => 120.98,
            ])
            ->assertRedirect(route('staff.home'));
    }

    public function test_driver_cannot_view_company_qr(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('settings/qr-ph.png', 'qr');
        Setting::setValue('qr_ph_path', 'settings/qr-ph.png');

        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->get(route('documents.qr-ph'))
            ->assertForbidden();
    }

    public function test_customer_can_submit_payment_screenshot_on_unpaid_invoice(): void
    {
        Storage::fake('local');

        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.bookings.payment-proof.store', $booking), [
                'proof' => UploadedFile::fake()->image('gcash.png'),
            ])
            ->assertRedirect(route('customer.bookings.show', $booking));

        $invoice->refresh();
        $this->assertSame(PaymentProofStatus::Pending, $invoice->payment_proof_status);
        $this->assertTrue($invoice->hasPendingPaymentProof());
        Storage::disk('local')->assertExists($invoice->payment_proof_path);
    }

    public function test_customer_cannot_submit_proof_for_another_customers_invoice(): void
    {
        Storage::fake('local');

        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['customer_id' => $owner->id]);
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($other)
            ->post(route('customer.bookings.payment-proof.store', $booking), [
                'proof' => UploadedFile::fake()->image('gcash.png'),
            ])
            ->assertForbidden();
    }

    public function test_staff_can_reject_proof_and_customer_can_resubmit(): void
    {
        Storage::fake('local');
        Mail::fake();
        config(['gk.mail_enabled' => true]);

        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($customer)->post(route('customer.bookings.payment-proof.store', $booking), [
            'proof' => UploadedFile::fake()->image('first.png'),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.invoice.reject-proof', $booking), [
                'notes' => 'Amount not in bank',
            ])
            ->assertRedirect(route('staff.bookings.show', $booking));

        $invoice->refresh();
        $this->assertSame(PaymentProofStatus::Rejected, $invoice->payment_proof_status);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertSame('Amount not in bank', $invoice->notes);

        Mail::assertSent(PaymentProofRejectedMail::class, fn (PaymentProofRejectedMail $mail) => $mail->hasTo($customer->email));

        $this->actingAs($customer)
            ->post(route('customer.bookings.payment-proof.store', $booking), [
                'proof' => UploadedFile::fake()->image('second.png'),
            ])
            ->assertRedirect(route('customer.bookings.show', $booking));

        $invoice->refresh();
        $this->assertSame(PaymentProofStatus::Pending, $invoice->payment_proof_status);
    }

    public function test_staff_can_mark_paid_without_a_screenshot(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();
        $invoice = Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.bookings.invoice.mark-paid', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }

    public function test_staff_overview_lists_pending_payment_proofs(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();
        Invoice::factory()->create([
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid,
            'payment_proof_path' => 'bookings/demo/proof.png',
            'payment_proof_status' => PaymentProofStatus::Pending,
            'payment_proof_submitted_at' => now('Asia/Manila'),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.home'))
            ->assertOk()
            ->assertSee('Pending payments')
            ->assertSee($booking->booking_number)
            ->assertSee('Payment proofs');
    }
}
