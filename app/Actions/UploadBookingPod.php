<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Pod;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadBookingPod
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function execute(Booking $booking, array $photos): Pod
    {
        $wasReplace = $booking->pod !== null;

        $pod = DB::transaction(function () use ($booking, $photos): Pod {
            $directory = 'bookings/'.$booking->booking_number.'/pod';

            $existing = $booking->pod;
            if ($existing !== null) {
                foreach ($existing->photo_paths as $oldPath) {
                    Storage::disk('local')->delete($oldPath);
                }
            }

            $photoPaths = [];
            foreach (array_values($photos) as $index => $photo) {
                $extension = strtolower($photo->getClientOriginalExtension());
                $filename = 'photo-'.($index + 1).'.'.$extension;
                Storage::disk('local')->putFileAs($directory, $photo, $filename);
                $photoPaths[] = $directory.'/'.$filename;
            }

            return Pod::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'photo_paths' => $photoPaths,
                    'captured_at' => now('Asia/Manila'),
                ],
            );
        });

        $this->activityLogger->log(
            action: $wasReplace ? 'booking.pod_replaced' : 'booking.pod_uploaded',
            subject: $booking,
            description: ($wasReplace ? 'POD replaced' : 'POD uploaded')." for {$booking->booking_number}.",
            properties: [
                'photo_count' => count($pod->photo_paths),
            ],
        );

        return $pod;
    }
}
