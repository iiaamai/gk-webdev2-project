<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Mail\BookingCreatedMail;
use App\Mail\BookingStatusChangedMail;
use App\Mail\GatepassUploadedMail;
use App\Mail\InvoiceIssuedMail;
use App\Mail\InvoicePaidMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Support\MailIntegration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sends lifecycle emails from email_events.txt.
 * No-ops while GK_MAIL_ENABLED is false (see development_notes.txt).
 */
class BookingEmailNotifier
{
    public function bookingCreated(Booking $booking): void
    {
        $this->send(function () use ($booking): void {
            $customer = $booking->customer;
            if ($customer === null) {
                return;
            }

            Mail::to($customer->email)->send(new BookingCreatedMail($booking));
        });
    }

    public function gatepassUploaded(Booking $booking): void
    {
        $this->send(function () use ($booking): void {
            $drivers = User::query()
                ->where('role', UserRole::Driver)
                ->whereHas('assignedVehicle', function ($query) use ($booking): void {
                    $query->where('pricing_id', $booking->pricing_id);
                })
                ->get();

            foreach ($drivers as $driver) {
                Mail::to($driver->email)->send(new GatepassUploadedMail($booking));
            }
        });
    }

    public function statusChanged(Booking $booking, BookingStatus $status, ?User $notifyDriver = null): void
    {
        if (! in_array($status, [
            BookingStatus::Accepted,
            BookingStatus::InTransit,
            BookingStatus::Completed,
            BookingStatus::Cancelled,
        ], true)) {
            return;
        }

        $this->send(function () use ($booking, $status, $notifyDriver): void {
            $booking->loadMissing(['customer', 'driver']);

            if ($booking->customer !== null) {
                Mail::to($booking->customer->email)->send(
                    new BookingStatusChangedMail($booking, $status, 'customer'),
                );
            }

            $driver = $notifyDriver ?? $booking->driver;

            if ($status === BookingStatus::Cancelled && $driver !== null) {
                Mail::to($driver->email)->send(
                    new BookingStatusChangedMail($booking, $status, 'driver'),
                );
            }
        });
    }

    public function invoiceIssued(Invoice $invoice): void
    {
        $this->send(function () use ($invoice): void {
            $invoice->loadMissing('booking.customer');
            $customer = $invoice->booking?->customer;

            if ($customer === null) {
                return;
            }

            Mail::to($customer->email)->send(new InvoiceIssuedMail($invoice));
        });
    }

    public function invoicePaid(Invoice $invoice): void
    {
        $this->send(function () use ($invoice): void {
            $invoice->loadMissing('booking.customer');
            $customer = $invoice->booking?->customer;

            if ($customer === null) {
                return;
            }

            Mail::to($customer->email)->send(new InvoicePaidMail($invoice));
        });
    }

    private function send(callable $callback): void
    {
        if (! MailIntegration::isEnabled()) {
            return;
        }

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);

            return;
        }

        $callback();
    }
}
