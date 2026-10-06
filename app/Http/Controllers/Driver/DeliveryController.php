<?php

namespace App\Http\Controllers\Driver;

use App\Actions\AcceptDriverBooking;
use App\Actions\UpdateDriverDeliveryStatus;
use App\Actions\UploadBookingEir;
use App\Actions\UploadBookingPod;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateDeliveryStatusRequest;
use App\Http\Requests\UploadEirRequest;
use App\Http\Requests\UploadPodRequest;
use App\Models\Booking;
use App\Services\BookingReceiptPdf;
use App\Services\BookingStaticRouteMapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $driver = $request->user();
        $driver->load('assignedVehicle.pricing');

        $available = Booking::query()
            ->availableForDriver($driver)
            ->with(['pricing', 'vehicle.pricing'])
            ->orderBy('created_at')
            ->get();

        $active = Booking::query()
            ->activeForDriver($driver)
            ->with(['pricing', 'vehicle.pricing'])
            ->orderByDesc('accepted_at')
            ->get();

        return view('driver.deliveries.index', compact('available', 'active', 'driver'));
    }

    public function show(Booking $booking, BookingStaticRouteMapService $routeMapService): View
    {
        $this->authorize('view', $booking);

        $booking->load(['eir', 'pod', 'pricing', 'vehicle.pricing', 'customer']);

        return view('driver.deliveries.show', [
            'booking' => $booking,
            'routeMap' => $routeMapService->forBooking($booking),
        ]);
    }

    public function accept(
        Booking $booking,
        AcceptDriverBooking $acceptDriverBooking,
        Request $request,
    ): RedirectResponse {
        $this->authorize('accept', $booking);

        $acceptDriverBooking->execute($request->user(), $booking);

        return redirect()
            ->route('driver.deliveries.show', $booking)
            ->with('status', 'Delivery accepted.');
    }

    public function updateStatus(
        UpdateDeliveryStatusRequest $request,
        Booking $booking,
        UpdateDriverDeliveryStatus $updateDriverDeliveryStatus,
    ): RedirectResponse {
        $status = BookingStatus::from($request->validated('status'));

        $updateDriverDeliveryStatus->execute($request->user(), $booking, $status);

        return redirect()
            ->route('driver.deliveries.show', $booking)
            ->with('status', 'Delivery status updated.');
    }

    public function storeEir(
        UploadEirRequest $request,
        Booking $booking,
        UploadBookingEir $uploadBookingEir,
    ): RedirectResponse {
        $uploadBookingEir->execute($booking, $request->file('eir'));

        return redirect()
            ->route('driver.deliveries.show', $booking)
            ->with('status', 'EIR uploaded.');
    }

    public function storePod(
        UploadPodRequest $request,
        Booking $booking,
        UploadBookingPod $uploadBookingPod,
    ): RedirectResponse {
        $uploadBookingPod->execute(
            $booking,
            $request->file('photos'),
        );

        return redirect()
            ->route('driver.deliveries.show', $booking)
            ->with('status', 'POD uploaded.');
    }

    public function downloadReceipt(
        Booking $booking,
        BookingReceiptPdf $bookingReceiptPdf,
    ): Response {
        $this->authorize('downloadReceipt', $booking);

        return $bookingReceiptPdf->download($booking);
    }
}
