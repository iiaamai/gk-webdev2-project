<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Pod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadBookingPod
{
    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function execute(Booking $booking, array $photos): Pod
    {
        return DB::transaction(function () use ($booking, $photos): Pod {
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
    }
}
