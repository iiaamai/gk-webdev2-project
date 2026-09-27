<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminOverviewQuery;
use App\Services\BookingStaticRouteMapService;
use App\Services\EarningsQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(
        Request $request,
        AdminOverviewQuery $adminOverviewQuery,
        BookingStaticRouteMapService $bookingStaticRouteMapService,
        EarningsQuery $earningsQuery,
    ): View {
        abort_unless($request->user()?->isSystemAdmin(), 403);

        $overview = $adminOverviewQuery->gather();
        $now = $adminOverviewQuery->nowInManila();

        $mapBookingId = $request->has('map_booking')
            ? $request->integer('map_booking')
            : null;

        $mapBooking = $adminOverviewQuery->resolveMapBooking(
            $overview['active_bookings'],
            $mapBookingId,
        );

        $routeMap = $mapBooking !== null
            ? $bookingStaticRouteMapService->forBooking($mapBooking)
            : null;

        $earningsSnapshot = $earningsQuery->forMonth((int) $now->year, (int) $now->month);

        return view('portals.admin-home', [
            'name' => $request->user()->name,
            'overview' => $overview,
            'mapBooking' => $mapBooking,
            'routeMap' => $routeMap,
            'selectedMapBookingId' => $mapBooking?->id,
            'earningsSnapshot' => $earningsSnapshot,
            'generatedAt' => $now,
        ]);
    }
}
