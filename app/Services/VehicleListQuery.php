<?php

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Support\ListFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class VehicleListQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $search = ListFilter::searchTerm($request);
        $status = VehicleStatus::tryFrom((string) $request->query('status', ''));

        return Vehicle::query()
            ->with(['driver', 'pricing'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('plate_number', 'like', '%'.$search.'%')
                        ->orWhere('brand', 'like', '%'.$search.'%')
                        ->orWhereHas('driver', function ($driver) use ($search): void {
                            $driver->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderBy('plate_number')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();
    }
}
