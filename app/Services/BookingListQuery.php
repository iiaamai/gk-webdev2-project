<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\ListFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class BookingListQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $search = ListFilter::searchTerm($request);
        $status = BookingStatus::tryFrom((string) $request->query('status', ''));

        return Booking::query()
            ->with(['customer', 'pricing', 'vehicle.pricing'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('booking_number', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function ($customer) use ($search): void {
                            $customer->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();
    }
}
