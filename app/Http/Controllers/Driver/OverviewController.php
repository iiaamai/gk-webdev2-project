<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\DriverOverviewQuery;
use App\Support\DriverTripCancelledNotice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, DriverOverviewQuery $driverOverviewQuery): View
    {
        abort_unless($request->user()?->isDriver(), 403);

        $driver = $request->user();
        $overview = $driverOverviewQuery->gather($driver);
        $cancelledNumber = DriverTripCancelledNotice::pull($driver);
        $cancelledBooking = DriverTripCancelledNotice::bookingFor($driver, $cancelledNumber);

        if ($cancelledNumber !== null) {
            session()->now(
                'status',
                'Delivery '.$cancelledNumber.' was cancelled. Return to overview — this trip is no longer yours to work.',
            );
        }

        return view('portals.driver-home', [
            'name' => $driver->name,
            'overview' => $overview,
            'cancelledBooking' => $cancelledBooking,
            'generatedAt' => $driverOverviewQuery->nowInManila(),
        ]);
    }
}
