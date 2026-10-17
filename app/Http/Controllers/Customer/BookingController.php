<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CreateCustomerBooking;
use App\Actions\SubmitInvoicePaymentProof;
use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreBookingRequest;
use App\Http\Requests\SubmitInvoicePaymentProofRequest;
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

        $customerId = $request->user()->id;

        $active = Booking::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', [
                BookingStatus::Pending,
                BookingStatus::Accepted,
                BookingStatus::InTransit,
            ])
            ->with(['pricing', 'vehicle.pricing', 'invoice'])
            ->orderByDesc('created_at')
            ->get();

        $history = Booking::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', [
                BookingStatus::Completed,
                BookingStatus::Cancelled,
            ])
            ->with(['pricing', 'vehicle.pricing', 'invoice'])
            ->orderByDesc('created_at')
            ->get();

        return view('customer.bookings.index', compact('active', 'history'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $pricings = Pricing::query()
            ->withCount([
                'vehicles as available_vehicles_count' => function ($query): void {
                    $query->where('status', VehicleStatus::Available);
                },
            ])
            ->orderBy('vehicle_type')
            ->get();

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

        $booking->load(['eir', 'pod', 'invoice', 'rating', 'driver']);

        return view('customer.bookings.show', [
            'booking' => $booking,
            'routeMap' => $routeMapService->forBooking($booking),
        ]);
    }

    public function downloadReceipt(
        Booking $booking,
        BookingReceiptPdf $bookingReceiptPdf,
    ): Response {
        $this->authorize('downloadReceipt', $booking);

        return $bookingReceiptPdf->download($booking);
    }

    public function storePaymentProof(
        SubmitInvoicePaymentProofRequest $request,
        Booking $booking,
        SubmitInvoicePaymentProof $submitInvoicePaymentProof,
    ): RedirectResponse {
        $submitInvoicePaymentProof->execute(
            $booking->invoice,
            $request->file('proof'),
        );

        return redirect()
            ->route('customer.bookings.show', $booking)
            ->with('status', 'Payment screenshot submitted. Staff will confirm when it arrives in our account.');
    }
}
