<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SyncVehicleDriverAssignment;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Requests\Admin\UpdateVehicleRequest;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::query()
            ->with(['driver', 'pricing'])
            ->orderBy('plate_number')
            ->get();

        return view('admin.fleet.index', compact('vehicles'));
    }

    public function create(): View
    {
        $this->authorize('create', Vehicle::class);

        return view('admin.fleet.create', [
            'drivers' => $this->assignableDrivers(),
            'pricings' => Pricing::query()->orderBy('vehicle_type')->get(),
        ]);
    }

    public function store(
        StoreVehicleRequest $request,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
    ): RedirectResponse {
        $data = $request->safe()->except(['driver_id']);
        $driverId = $request->validated('driver_id');

        $vehicle = Vehicle::query()->create($data);

        $syncVehicleDriverAssignment->forVehicle(
            $vehicle,
            $driverId !== null ? (int) $driverId : null,
        );

        return redirect()
            ->route('admin.fleet.index')
            ->with('status', 'Vehicle created.');
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('update', $vehicle);

        $vehicle->load('driver');

        return view('admin.fleet.edit', [
            'vehicle' => $vehicle,
            'drivers' => $this->assignableDrivers($vehicle),
            'pricings' => Pricing::query()->orderBy('vehicle_type')->get(),
        ]);
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
    ): RedirectResponse {
        $data = $request->safe()->except(['driver_id']);
        $driverId = $request->validated('driver_id');

        $vehicle->update($data);

        $syncVehicleDriverAssignment->forVehicle(
            $vehicle->fresh(['pricing']),
            $driverId !== null ? (int) $driverId : null,
        );

        return redirect()
            ->route('admin.fleet.index')
            ->with('status', 'Vehicle updated.');
    }

    public function destroy(
        Vehicle $vehicle,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
    ): RedirectResponse {
        $this->authorize('delete', $vehicle);

        $syncVehicleDriverAssignment->forVehicle($vehicle, null);
        $vehicle->archive();

        return redirect()
            ->route('admin.fleet.index')
            ->with('status', 'Vehicle archived.');
    }

    /**
     * @return Collection<int, User>
     */
    private function assignableDrivers(?Vehicle $vehicle = null)
    {
        return User::query()
            ->where('role', UserRole::Driver)
            ->where(function ($query) use ($vehicle): void {
                $query->whereDoesntHave('assignedVehicle');

                if ($vehicle?->driver_id) {
                    $query->orWhereKey($vehicle->driver_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
