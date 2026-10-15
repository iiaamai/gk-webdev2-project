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
use App\Services\ActivityLogger;
use App\Services\PermanentDeleteGuard;
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

        return view('admin.fleet.index', [
            'vehicles' => $vehicles,
            'filtersActive' => $filtersActive,
            'search' => ListFilter::searchTerm($request),
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Vehicle::class);

        return view('admin.fleet.create', [
            'drivers' => $this->fleetDrivers(),
            'pricings' => Pricing::query()->orderBy('vehicle_type')->get(),
        ]);
    }

    public function store(
        StoreVehicleRequest $request,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $data = $request->safe()->except(['driver_id']);
        $driverId = $request->validated('driver_id');

        $vehicle = Vehicle::query()->create($data);

        $syncVehicleDriverAssignment->forVehicle(
            $vehicle,
            $driverId !== null ? (int) $driverId : null,
        );

        $activityLogger->log(
            action: 'fleet.created',
            subject: $vehicle,
            description: "Vehicle {$vehicle->plate_number} created.",
            request: $request,
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
            'drivers' => $this->fleetDrivers(),
            'pricings' => Pricing::query()->orderBy('vehicle_type')->get(),
        ]);
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $data = $request->safe()->except(['driver_id']);
        $driverId = $request->validated('driver_id');

        $vehicle->update($data);

        $syncVehicleDriverAssignment->forVehicle(
            $vehicle->fresh(['pricing']),
            $driverId !== null ? (int) $driverId : null,
        );

        $activityLogger->log(
            action: 'fleet.updated',
            subject: $vehicle,
            description: "Vehicle {$vehicle->plate_number} updated.",
            request: $request,
        );

        return redirect()
            ->route('admin.fleet.index')
            ->with('status', 'Vehicle updated.');
    }

    public function destroy(
        Vehicle $vehicle,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $this->authorize('delete', $vehicle);

        $plate = $vehicle->plate_number;
        $syncVehicleDriverAssignment->forVehicle($vehicle, null);
        $vehicle->archive();

        $activityLogger->log(
            action: 'fleet.archived',
            subject: $vehicle,
            description: "Vehicle {$plate} archived.",
        );

        return redirect()
            ->route('admin.fleet.index')
            ->with('status', 'Vehicle archived.');
    }

    public function archived(): View
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::onlyArchived()
            ->with(['driver' => fn ($query) => $query->withArchived(), 'pricing' => fn ($query) => $query->withArchived()])
            ->orderByDesc('archived_at')
            ->paginate(ListFilter::PER_PAGE);

        return view('admin.fleet.archived', [
            'vehicles' => $vehicles,
        ]);
    }

    public function restore(Vehicle $archivedVehicle, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('restore', $archivedVehicle);

        $archivedVehicle->unarchive();

        $activityLogger->log(
            action: 'fleet.restored',
            subject: $archivedVehicle,
            description: "Vehicle {$archivedVehicle->plate_number} restored.",
        );

        return redirect()
            ->route('admin.fleet.archived')
            ->with('status', 'Vehicle restored.');
    }

    public function forceDestroy(
        Vehicle $archivedVehicle,
        PermanentDeleteGuard $permanentDeleteGuard,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $this->authorize('forceDelete', $archivedVehicle);

        $message = $permanentDeleteGuard->vehicle($archivedVehicle);

        if ($message !== null) {
            return redirect()
                ->route('admin.fleet.archived')
                ->withErrors(['archive' => $message]);
        }

        $plate = $archivedVehicle->plate_number;
        $archivedVehicle->delete();

        $activityLogger->log(
            action: 'fleet.deleted',
            description: "Vehicle {$plate} permanently deleted.",
        );

        return redirect()
            ->route('admin.fleet.archived')
            ->with('status', 'Vehicle permanently deleted.');
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
