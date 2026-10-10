<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateLocationRequest;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function store(UpdateLocationRequest $request): JsonResponse
    {
        $request->user()->update([
            'location_lat' => $request->validated('lat'),
            'location_lng' => $request->validated('lng'),
            'location_updated_at' => now('Asia/Manila'),
        ]);

        return response()->json(['ok' => true]);
    }
}
