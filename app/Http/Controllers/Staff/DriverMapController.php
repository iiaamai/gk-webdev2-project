<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\DriverLocationQuery;
use App\Support\MapboxIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverMapController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isStaff(), 403);

        return view('fleet.driver-map', [
            'locationsUrl' => route('staff.drivers.locations'),
            'mapboxReady' => MapboxIntegration::isConfigured(),
        ]);
    }

    public function locations(Request $request, DriverLocationQuery $driverLocationQuery): JsonResponse
    {
        abort_unless($request->user()?->isStaff(), 403);

        return response()->json([
            'drivers' => $driverLocationQuery->latest(),
        ]);
    }
}
