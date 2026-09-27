<?php

namespace App\Http\Controllers\Staff;

use App\Actions\CancelBooking;
use App\Actions\MarkInvoicePaid;
use App\Actions\UpdateBooking;
use App\Actions\UploadBookingGatepass;
use App\Http\Controllers\Controller;
use App\Http\Requests\MarkInvoicePaidRequest;
use App\Http\Requests\Staff\UpdateBookingRequest;
use App\Http\Requests\UploadGatepassRequest;
use App\Models\Booking;
use App\Models\Pricing;
use App\Services\BookingReceiptPdf;
use App\Services\BookingStaticRouteMapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->with(['customer', 'pricing', 'vehicle.pricing'])
            ->orderByDesc('created_at')
            ->get();

        return view('staff.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking, BookingStaticRouteMapService $routeMapService): View
    {
        $this->authorize('view', $booking);

        $booking->load(['customer', 'eir', 'pod', 'invoice', 'pricing', 'vehicle.pricing']);

        return view('staff.bookings.show', [
            'booking' => $booking,
            'routeMap' => $routeMapService->forBooking($booking),
        ]);
    }

    public function edit(Booking $booking, BookingStaticRouteMapService $routeMapService): View
    {
        $this->authorize('view', $booking);

        $booking->load(['customer', 'pricing', 'vehicle.pricing', 'eir', 'pod', 'invoice']);
        $pricings = Pricing::query()->orderBy('vehicle_type')->get();

        return view('staff.bookings.edit', [
            'booking' => $booking,
            'pricings' => $pricings,
            'routeMap' => $routeMapService->forBooking($booking),
        ]);
    }

    public function update(
        UpdateBookingRequest $request,
        Booking $booking,
        UpdateBooking $updateBooking,
    ): RedirectResponse {
        $updateBooking->execute($booking, $request->validated());

        return redirect()
            ->route('staff.bookings.edit', $booking)
            ->with('status', 'Booking updated.');
    }

    public function storeGatepass(
        UploadGatepassRequest $request,
        Booking $booking,
        UploadBookingGatepass $uploadBookingGatepass,
    ): RedirectResponse {
        $wasReplace = $booking->hasGatepass();

        $uploadBookingGatepass->execute(
            $booking,
            $request->file('gatepass'),
            allowReplace: $wasReplace,
        );

        return redirect()
            ->route('staff.bookings.edit', $booking)
            ->with('status', $wasReplace ? 'Gatepass replaced.' : 'Gatepass uploaded.');
    }

    public function cancel(Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $cancelBooking->execute($booking);

        return redirect()
            ->route('staff.bookings.show', $booking)
            ->with('status', 'Booking cancelled.');
    }

    public function markInvoicePaid(
        MarkInvoicePaidRequest $request,
        Booking $booking,
        MarkInvoicePaid $markInvoicePaid,
    ): RedirectResponse {
        $markInvoicePaid->execute($booking->invoice, $request->validated());

        return redirect()
            ->route('staff.bookings.edit', $booking)
            ->with('status', 'Invoice marked as paid.');
    }

    public function downloadReceipt(
        Booking $booking,
        BookingReceiptPdf $bookingReceiptPdf,
    ): Response {
        $this->authorize('downloadReceipt', $booking);

        return $bookingReceiptPdf->download($booking);
    }
}
