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

        return Storage::disk('local')->download(
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

        return Storage::disk('local')->download(
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

        $path = $pod->photo_paths[$index];

        return Storage::disk('local')->download(
            $path,
            $booking->booking_number.'-pod-photo-'.($index + 1),
        );
    }

    public function podSignature(Booking $booking): StreamedResponse
    {
        $this->authorize('viewPod', $booking);

        $pod = $booking->pod;
        if ($pod === null) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $pod->signature_path,
            $booking->booking_number.'-pod-signature',
        );
    }
}
