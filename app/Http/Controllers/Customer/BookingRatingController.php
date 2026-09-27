<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CreateBookingRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreBookingRatingRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

class BookingRatingController extends Controller
{
    public function store(
        StoreBookingRatingRequest $request,
        Booking $booking,
        CreateBookingRating $createBookingRating,
    ): RedirectResponse {
        $createBookingRating->execute(
            $request->user(),
            $booking,
            $request->validated(),
        );

        return redirect()
            ->route('customer.bookings.show', $booking)
            ->with('status', 'Thank you for your rating.');
    }
}
