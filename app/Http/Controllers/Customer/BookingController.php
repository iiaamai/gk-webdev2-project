<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CreateCustomerBooking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Pricing;
use App\Services\BookingStaticRouteMapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->where('customer_id', $request->user()->id)
            ->with(['pricing', 'vehicle.pricing'])
            ->orderByDesc('created_at')
            ->get();

        return view('customer.bookings.index', compact('bookings'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $pricings = Pricing::query()->orderBy('vehicle_type')->get();

        return view('customer.bookings.create', compact('pricings'));
    }

    public function store(StoreBookingRequest $request, CreateCustomerBooking $createCustomerBooking): RedirectResponse
    {
        $booking = $createCustomerBooking->execute(
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('customer.bookings.show', $booking)
            ->with('status', 'Booking created successfully.');
    }

    public function show(Booking $booking, BookingStaticRouteMapService $routeMapService): View
    {
        $this->authorize('view', $booking);

        $booking->load(['eir', 'pod', 'invoice', 'rating']);

        return view('customer.bookings.show', [
            'booking' => $booking,
            'routeMap' => $routeMapService->forBooking($booking),
        ]);
    }
}
