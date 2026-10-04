<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\BookingStaticRouteMapService;
use App\Services\StaffOverviewQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(
        Request $request,
        StaffOverviewQuery $staffOverviewQuery,
        BookingStaticRouteMapService $bookingStaticRouteMapService,
    ): View {
        abort_unless($request->user()?->isStaff(), 403);

        $overview = $staffOverviewQuery->gather();

        $mapBookingId = $request->has('map_booking')
            ? $request->integer('map_booking')
            : null;

        $mapBooking = $staffOverviewQuery->resolveMapBooking(
            $overview['active_bookings'],
            $mapBookingId,
        );

        $routeMap = $mapBooking !== null
            ? $bookingStaticRouteMapService->forBooking($mapBooking)
            : null;

        return view('portals.staff-home', [
            'name' => $request->user()->name,
            'overview' => $overview,
            'mapBooking' => $mapBooking,
            'routeMap' => $routeMap,
            'selectedMapBookingId' => $mapBooking?->id,
            'generatedAt' => $staffOverviewQuery->nowInManila(),
        ]);
    }
}
