<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelBooking;
use App\Actions\CreateAdminBooking;
use App\Actions\MarkInvoicePaid;
use App\Actions\MarkInvoiceUnpaid;
use App\Actions\UpdateBooking;
use App\Actions\UpdateBookingStatus;
use App\Actions\UploadBookingEir;
use App\Actions\UploadBookingGatepass;
use App\Actions\UploadBookingPod;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookingRequest;
use App\Http\Requests\Admin\UpdateBookingRequest;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Http\Requests\MarkInvoicePaidRequest;
use App\Http\Requests\MarkInvoiceUnpaidRequest;
use App\Http\Requests\UploadEirRequest;
use App\Http\Requests\UploadGatepassRequest;
use App\Http\Requests\UploadPodRequest;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\User;
use App\Services\BookingListQuery;
use App\Services\BookingReceiptPdf;
use App\Services\BookingStaticRouteMapService;
use App\Support\ListFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request, BookingListQuery $bookingListQuery): View
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = $bookingListQuery->paginate($request);
        $filtersActive = ListFilter::isActive($request, ['q', 'status', 'scope']);

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filtersActive' => $filtersActive,
            'search' => ListFilter::searchTerm($request),
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Booking::class);

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->orderBy('name')
            ->get();
        $pricings = Pricing::query()->orderBy('vehicle_type')->get();

        $drivers = User::query()
            ->where('role', UserRole::Driver)
            ->with('assignedVehicle')
            ->orderBy('name')
            ->get();

        return view('admin.bookings.create', [
            'customers' => $customers,
            'drivers' => $drivers,
            'pricings' => $pricings,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        CreateAdminBooking $createAdminBooking,
        UploadBookingGatepass $uploadBookingGatepass,
    ): RedirectResponse {
        $data = $request->safe()->except(['gatepass']);
        $gatepass = $request->file('gatepass');

        $booking = $createAdminBooking->execute($data);

        if ($gatepass !== null && $booking->status === BookingStatus::Pending) {
            $uploadBookingGatepass->execute($booking, $gatepass, allowReplace: false);
        }

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'Booking created.');
    }

    public function show(Booking $booking, BookingStaticRouteMapService $routeMapService): View
    {
        $this->authorize('view', $booking);

        $booking->load(['customer', 'eir', 'pod', 'invoice', 'rating', 'pricing', 'vehicle.pricing', 'driver']);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'statuses' => BookingStatus::cases(),
            'routeMap' => $routeMapService->forBooking($booking),
        ]);
    }

    public function edit(Booking $booking, BookingStaticRouteMapService $routeMapService): View
    {
        $this->authorize('update', $booking);

        $booking->load(['customer', 'driver', 'pricing', 'vehicle.pricing', 'eir', 'pod']);
        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->orderBy('name')
            ->get();
        $pricings = Pricing::query()->orderBy('vehicle_type')->get();
        $drivers = User::query()
            ->where('role', UserRole::Driver)
            ->with('assignedVehicle')
            ->orderBy('name')
            ->get();

        return view('admin.bookings.edit', [
            'booking' => $booking,
            'customers' => $customers,
            'drivers' => $drivers,
            'pricings' => $pricings,
            'statuses' => BookingStatus::cases(),
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
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'Booking updated.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        $booking->archive();

        return redirect()
            ->route('admin.bookings.index')
            ->with('status', 'Booking archived.');
    }

    public function storeGatepass(
        UploadGatepassRequest $request,
        Booking $booking,
        UploadBookingGatepass $uploadBookingGatepass,
    ): RedirectResponse {
        $uploadBookingGatepass->execute(
            $booking,
            $request->file('gatepass'),
            allowReplace: $booking->hasGatepass(),
        );

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'Gatepass saved.');
    }

    public function updateStatus(
        UpdateBookingStatusRequest $request,
        Booking $booking,
        UpdateBookingStatus $updateBookingStatus,
    ): RedirectResponse {
        $status = BookingStatus::from($request->validated('status'));
        $updateBookingStatus->execute($booking, $status);

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'Status updated.');
    }

    public function cancel(Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $cancelBooking->execute($booking);

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', 'Booking cancelled.');
    }

    public function storeEir(
        UploadEirRequest $request,
        Booking $booking,
        UploadBookingEir $uploadBookingEir,
    ): RedirectResponse {
        $uploadBookingEir->execute($booking, $request->file('eir'));

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'EIR saved.');
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
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'POD saved.');
    }

    public function downloadReceipt(
        Booking $booking,
        BookingReceiptPdf $bookingReceiptPdf,
    ): Response {
        $this->authorize('downloadReceipt', $booking);

        return $bookingReceiptPdf->download($booking);
    }

    public function markInvoicePaid(
        MarkInvoicePaidRequest $request,
        Booking $booking,
        MarkInvoicePaid $markInvoicePaid,
    ): RedirectResponse {
        $markInvoicePaid->execute($booking->invoice, $request->validated());

        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '') {
            return redirect()
                ->to($redirectTo)
                ->with('status', 'Invoice marked as paid.');
        }

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', 'Invoice marked as paid.');
    }

    public function markInvoiceUnpaid(
        MarkInvoiceUnpaidRequest $request,
        Booking $booking,
        MarkInvoiceUnpaid $markInvoiceUnpaid,
    ): RedirectResponse {
        $markInvoiceUnpaid->execute($booking->invoice, $request->validated());

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('status', 'Invoice reverted to unpaid.');
    }
}
