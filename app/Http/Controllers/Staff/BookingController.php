<?php

namespace App\Http\Controllers\Staff;

use App\Actions\CancelBooking;
use App\Actions\UpdateBooking;
use App\Actions\UploadBookingGatepass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\UpdateBookingRequest;
use App\Http\Requests\UploadGatepassRequest;
use App\Models\Booking;
use App\Models\Pricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->with('customer')
            ->orderByDesc('created_at')
            ->get();

        return view('staff.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load(['customer', 'eir', 'pod']);
        $pricings = Pricing::query()->orderBy('vehicle_type')->get();

        return view('staff.bookings.show', compact('booking', 'pricings'));
    }

    public function update(
        UpdateBookingRequest $request,
        Booking $booking,
        UpdateBooking $updateBooking,
    ): RedirectResponse {
        $updateBooking->execute($booking, $request->validated());

        return redirect()
            ->route('staff.bookings.show', $booking)
            ->with('status', 'Booking updated.');
    }

    public function storeGatepass(
        UploadGatepassRequest $request,
        Booking $booking,
        UploadBookingGatepass $uploadBookingGatepass,
    ): RedirectResponse {
        $uploadBookingGatepass->execute($booking, $request->file('gatepass'));

        return redirect()
            ->route('staff.bookings.show', $booking)
            ->with('status', 'Gatepass uploaded.');
    }

    public function cancel(Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $cancelBooking->execute($booking);

        return redirect()
            ->route('staff.bookings.show', $booking)
            ->with('status', 'Booking cancelled.');
    }
}
