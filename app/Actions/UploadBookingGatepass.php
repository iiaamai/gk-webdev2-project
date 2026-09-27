<?php

namespace App\Actions;

use App\Models\Booking;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadBookingGatepass
{
    public function __construct(
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Booking $booking, UploadedFile $file, bool $allowReplace = false): Booking
    {
        if ($booking->hasGatepass() && ! $allowReplace) {
            throw ValidationException::withMessages([
                'gatepass' => 'A gatepass is already uploaded. Only an administrator can replace it.',
            ]);
        }

        $wasFirstUpload = ! $booking->hasGatepass();

        $booking = DB::transaction(function () use ($booking, $file, $allowReplace): Booking {
            $directory = 'bookings/'.$booking->booking_number;
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = 'gatepass.'.$extension;
            $path = $directory.'/'.$filename;

            if ($allowReplace && filled($booking->gatepass_path)) {
                Storage::disk('local')->delete($booking->gatepass_path);
            }

            Storage::disk('local')->putFileAs($directory, $file, $filename);

            $booking->update(['gatepass_path' => $path]);

            return $booking->fresh();
        });

        if ($wasFirstUpload) {
            $this->bookingEmailNotifier->gatepassUploaded($booking);
        }

        $this->activityLogger->log(
            action: $wasFirstUpload ? 'booking.gatepass_uploaded' : 'booking.gatepass_replaced',
            subject: $booking,
            description: $wasFirstUpload
                ? "Gatepass uploaded for {$booking->booking_number}."
                : "Gatepass replaced for {$booking->booking_number}.",
        );

        return $booking;
    }
}
