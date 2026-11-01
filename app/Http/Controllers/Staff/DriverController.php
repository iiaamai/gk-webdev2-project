<?php

namespace App\Http\Controllers\Staff;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DriverAttendance;
use App\Support\ListFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $search = ListFilter::searchTerm($request);
        $filtersActive = ListFilter::isActive($request, ['q']);

        $drivers = User::query()
            ->where('role', UserRole::Driver)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->with('assignedVehicle.pricing')
            ->orderBy('name')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();

        $statuses = $drivers->getCollection()->mapWithKeys(
            fn (User $driver): array => [$driver->id => DriverAttendance::statusFor($driver)],
        );

        return view('staff.drivers.index', [
            'drivers' => $drivers,
            'statuses' => $statuses,
            'filtersActive' => $filtersActive,
            'search' => $search,
        ]);
    }
}
