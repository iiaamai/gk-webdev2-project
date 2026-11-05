<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Setting;
use App\Support\BookingReceiptBreakdown;
use App\Support\MetricWeight;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Builds a printable delivery-receipt PDF from Blade HTML + CSS (Dompdf).
 */
class BookingReceiptPdf
{
    public function download(Booking $booking): Response
    {
        $booking->loadMissing(['customer', 'driver', 'pricing', 'invoice', 'vehicle', 'rating']);

        $amount = (float) ($booking->invoice?->amount ?? $booking->pricing?->amount ?? 0);
        $logoPath = public_path('images/gk-logo-1.png');

        $data = [
            'issuedAt' => now('Asia/Manila')->format('M j, Y g:i A'),
            'customerName' => $booking->customer?->name ?? 'Archived customer',
            'customerEmail' => $booking->customer?->email ?? '—',
            'bookingNumber' => $booking->booking_number,
            'tripStatus' => $booking->status->label(),
            'total' => $amount,
            'vehicleType' => $booking->pricing?->vehicle_type ?? $booking->vehicle_type ?? '—',
            'plate' => $booking->vehicle?->plate_number,
            'pickupAt' => $booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'pickup' => $booking->pickup_address,
            'dropoff' => $booking->dropoff_address,
            'portNumber' => $booking->pickup_port_number,
            'containerNumber' => $booking->pickup_container_number,
            'driverName' => $booking->driver?->name,
            'ratingScore' => $booking->rating?->score,
            'breakdown' => BookingReceiptBreakdown::lines($booking),
            'paymentStatus' => $booking->invoice?->status?->label() ?? '—',
            'cargoDesc' => filled($booking->cargo_desc) ? $booking->cargo_desc : null,
            'cargoWeight' => $booking->cargo_kg
                ? MetricWeight::weightLabel((int) $booking->cargo_kg)
                : null,
            'companyName' => Setting::getValue('company_name', 'GK Trucking Services'),
            'supportEmail' => Setting::getValue('support_email'),
            'logoPath' => $logoPath,
            'logoExists' => is_file($logoPath),
        ];

        $pdf = Pdf::loadView('pdf.delivery-receipt', $data)->setPaper('a4');

        $filename = $booking->booking_number.'-receipt.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
