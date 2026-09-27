<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Response;

/**
 * Builds a simple printable delivery-receipt PDF (no external PDF library).
 */
class BookingReceiptPdf
{
    public function download(Booking $booking): Response
    {
        $booking->loadMissing(['customer', 'driver', 'pricing', 'invoice']);

        $issuedAt = now('Asia/Manila')->format('M j, Y g:i A');
        $amount = $booking->invoice?->amount ?? $booking->pricing?->amount;
        $invoiceStatus = $booking->invoice?->status?->value ?? '—';

        $lines = [
            'GK Trucking Services',
            'Delivery Receipt',
            '',
            'Issued: '.$issuedAt,
            'Booking: '.$booking->booking_number,
            'Status: '.$booking->status->value,
            '',
            'Customer: '.$booking->customer->name,
            'Email: '.$booking->customer->email,
            '',
            'Vehicle type: '.$booking->vehicle_type,
            'Driver: '.($booking->driver?->name ?? 'Unassigned'),
            '',
            'Pickup: '.$booking->pickup_address,
            'Dropoff: '.$booking->dropoff_address,
            'Preferred pickup: '.$booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A'),
            '',
            'Invoice amount: PHP '.number_format((float) ($amount ?? 0), 2),
            'Invoice status: '.$invoiceStatus,
        ];

        if (filled($booking->cargo_desc)) {
            $lines[] = '';
            $lines[] = 'Cargo: '.$booking->cargo_desc;
        }

        $pdf = $this->render($lines);
        $filename = $booking->booking_number.'-receipt.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<string>  $lines
     */
    private function render(array $lines): string
    {
        $logo = $this->logoJpeg();
        $hasLogo = $logo !== null;

        $textY = $hasLogo ? 700 : 780;
        $content = "BT\n/F1 11 Tf\n14 TL\n50 {$textY} Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "T*\n";
            }

            $content .= '('.$this->escape($line).") Tj\n";
        }

        $content .= 'ET';

        if ($hasLogo) {
            $content = "q\n48 0 0 48 50 730 cm\n/Im1 Do\nQ\n".$content;
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';

        if ($hasLogo) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> /XObject << /Im1 6 0 R >> >> >>';
            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
            $objects[] = '<< /Type /XObject /Subtype /Image /Width '.$logo['width']
                .' /Height '.$logo['height']
                .' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '
                .strlen($logo['data'])." >>\nstream\n".$logo['data']."\nendstream";
        } else {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer'."\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    /**
     * @return array{data: string, width: int, height: int}|null
     */
    private function logoJpeg(): ?array
    {
        $path = public_path('images/logo-placeholder.png');

        if (! is_file($path) || ! function_exists('imagecreatefrompng')) {
            return null;
        }

        $image = @imagecreatefrompng($path);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 90);
        $data = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        if ($data === false || $data === '') {
            return null;
        }

        return [
            'data' => $data,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function escape(string $text): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii === false) {
            $ascii = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
    }
}
