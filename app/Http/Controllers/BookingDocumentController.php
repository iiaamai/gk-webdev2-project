<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingDocumentController extends Controller
{
    public function gatepass(Booking $booking): StreamedResponse
    {
        $this->authorize('viewGatepass', $booking);

        if (! $booking->hasGatepass()) {
            abort(404);
        }

        return $this->streamInline(
            $booking->gatepass_path,
            $booking->booking_number.'-gatepass',
        );
    }

    public function eir(Booking $booking): StreamedResponse
    {
        $this->authorize('viewEir', $booking);

        $eir = $booking->eir;
        if ($eir === null) {
            abort(404);
        }

        return $this->streamInline(
            $eir->eir_path,
            $booking->booking_number.'-eir',
        );
    }

    public function podPhoto(Booking $booking, int $index): StreamedResponse
    {
        $this->authorize('viewPod', $booking);

        $pod = $booking->pod;
        if ($pod === null || ! isset($pod->photo_paths[$index])) {
            abort(404);
        }

        return $this->streamInline(
            $pod->photo_paths[$index],
            $booking->booking_number.'-pod-photo-'.($index + 1),
        );
    }

    private function streamInline(string $path, string $filename): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        $mimeType = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk('local')->response(
            $path,
            $filename,
            ['Content-Type' => $mimeType],
            'inline',
        );
    }
}
