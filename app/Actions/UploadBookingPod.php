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
    public function execute(Booking $booking, array $photos, UploadedFile $signature): Pod
    {
        return DB::transaction(function () use ($booking, $photos, $signature): Pod {
            $directory = 'bookings/'.$booking->booking_number.'/pod';

            $existing = $booking->pod;
            if ($existing !== null) {
                foreach ($existing->photo_paths as $oldPath) {
                    Storage::disk('local')->delete($oldPath);
                }
                Storage::disk('local')->delete($existing->signature_path);
            }

            $photoPaths = [];
            foreach (array_values($photos) as $index => $photo) {
                $extension = strtolower($photo->getClientOriginalExtension());
                $filename = 'photo-'.($index + 1).'.'.$extension;
                Storage::disk('local')->putFileAs($directory, $photo, $filename);
                $photoPaths[] = $directory.'/'.$filename;
            }

            $signatureExtension = strtolower($signature->getClientOriginalExtension());
            $signatureFilename = 'signature.'.$signatureExtension;
            Storage::disk('local')->putFileAs($directory, $signature, $signatureFilename);
            $signaturePath = $directory.'/'.$signatureFilename;

            return Pod::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'photo_paths' => $photoPaths,
                    'signature_path' => $signaturePath,
                    'captured_at' => now('Asia/Manila'),
                ],
            );
        });
    }
}
