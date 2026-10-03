<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\DriverOverviewQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, DriverOverviewQuery $driverOverviewQuery): View
    {
        abort_unless($request->user()?->isDriver(), 403);

        $driver = $request->user();
        $overview = $driverOverviewQuery->gather($driver);

        return view('portals.driver-home', [
            'name' => $driver->name,
            'overview' => $overview,
            'generatedAt' => $driverOverviewQuery->nowInManila(),
        ]);
    }
}
