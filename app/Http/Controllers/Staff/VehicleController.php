<?php

namespace App\Http\Controllers\Staff;

use App\Actions\SyncVehicleDriverAssignment;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\UpdateVehicleRequest;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleListQuery;
use App\Support\ListFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request, VehicleListQuery $vehicleListQuery): View
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = $vehicleListQuery->paginate($request);
        $filtersActive = ListFilter::isActive($request, ['q', 'status']);

        return view('staff.fleet.index', [
            'vehicles' => $vehicles,
            'filtersActive' => $filtersActive,
            'search' => ListFilter::searchTerm($request),
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('update', $vehicle);

        $vehicle->load('driver');
        $isInUse = $vehicle->status === VehicleStatus::InUse;

        return view('staff.fleet.edit', [
            'vehicle' => $vehicle,
            'drivers' => $this->fleetDrivers(),
            'pricings' => Pricing::query()->orderBy('vehicle_type')->get(),
            'lockDetails' => true,
            'canEditStatus' => ! $isInUse,
            'canEditDriver' => ! $isInUse,
            'showSubmit' => ! $isInUse,
            'isReadOnly' => $isInUse,
        ]);
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
    ): RedirectResponse {
        $this->authorize('updateStatus', $vehicle);

        $canUpdateDriver = $request->user()?->can('updateDriver', $vehicle) ?? false;
        $status = VehicleStatus::from($request->validated('status'));

        $vehicle->update(['status' => $status]);

        if ($canUpdateDriver) {
            $driverId = $request->validated('driver_id');

            $syncVehicleDriverAssignment->forVehicle(
                $vehicle->fresh(['pricing']),
                $driverId !== null ? (int) $driverId : null,
            );
        }

        return redirect()
            ->route('staff.fleet.index')
            ->with('status', 'Vehicle updated.');
    }

    /**
     * @return Collection<int, User>
     */
    private function fleetDrivers(): Collection
    {
        return User::query()
            ->where('role', UserRole::Driver)
            ->with('assignedVehicle')
            ->orderBy('name')
            ->get();
    }
}
