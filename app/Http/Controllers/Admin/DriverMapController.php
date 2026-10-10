<?php

namespace App\Http\Controllers\Admin;

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
        abort_unless($request->user()?->isSystemAdmin(), 403);

        return view('fleet.driver-map', [
            'locationsUrl' => route('admin.drivers.locations'),
            'mapboxReady' => MapboxIntegration::isConfigured(),
        ]);
    }

    public function locations(Request $request, DriverLocationQuery $driverLocationQuery): JsonResponse
    {
        abort_unless($request->user()?->isSystemAdmin(), 403);

        return response()->json([
            'drivers' => $driverLocationQuery->latest(),
        ]);
    }
}
