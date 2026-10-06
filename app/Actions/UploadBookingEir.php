<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Eir;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadBookingEir
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Booking $booking, UploadedFile $file): Eir
    {
        $wasReplace = $booking->eir !== null;

        $eir = DB::transaction(function () use ($booking, $file): Eir {
            $directory = 'bookings/'.$booking->booking_number;
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = 'eir.'.$extension;
            $path = $directory.'/'.$filename;

            $existing = $booking->eir;
            if ($existing !== null && filled($existing->eir_path)) {
                Storage::disk('local')->delete($existing->eir_path);
            }

            Storage::disk('local')->putFileAs($directory, $file, $filename);

            return Eir::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'eir_path' => $path,
                    'uploaded_at' => now('Asia/Manila'),
                ],
            );
        });

        $this->activityLogger->log(
            action: $wasReplace ? 'booking.eir_replaced' : 'booking.eir_uploaded',
            subject: $booking,
            description: ($wasReplace ? 'EIR replaced' : 'EIR uploaded')." for {$booking->booking_number}.",
        );

        return $eir;
    }
}
