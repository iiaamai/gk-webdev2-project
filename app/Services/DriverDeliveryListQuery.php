<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Support\ListFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class DriverDeliveryListQuery
{
    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        $statuses = [BookingStatus::Pending, ...BookingStatus::activeDeliveryStatuses()];
        $options = [];
        foreach ($statuses as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    public function paginateAvailable(User $driver, Request $request): LengthAwarePaginator
    {
        $search = ListFilter::searchTerm($request);
        $status = BookingStatus::tryFrom((string) $request->query('status', ''));

        $query = Booking::query()
            ->availableForDriver($driver)
            ->with(['pricing', 'vehicle.pricing']);

        if ($search !== '') {
            $query->where('booking_number', 'like', '%'.$search.'%');
        }

        if ($status !== null && $status !== BookingStatus::Pending) {
            $query->whereRaw('0 = 1');
        }

        return $query
            ->orderBy('created_at')
            ->paginate(ListFilter::PER_PAGE, ['*'], 'available_page')
            ->withQueryString();
    }

    public function paginateActive(User $driver, Request $request): LengthAwarePaginator
    {
        $search = ListFilter::searchTerm($request);
        $status = BookingStatus::tryFrom((string) $request->query('status', ''));
        $allowedActive = array_map(
            fn (BookingStatus $bookingStatus): string => $bookingStatus->value,
            BookingStatus::activeDeliveryStatuses(),
        );

        $query = Booking::query()
            ->activeForDriver($driver)
            ->with(['pricing', 'vehicle.pricing']);

        if ($search !== '') {
            $query->where('booking_number', 'like', '%'.$search.'%');
        }

        if ($status !== null) {
            if ($status === BookingStatus::Pending) {
                $query->whereRaw('0 = 1');
            } elseif (in_array($status->value, $allowedActive, true)) {
                $query->where('status', $status);
            }
        }

        return $query
            ->orderByDesc('accepted_at')
            ->paginate(ListFilter::PER_PAGE, ['*'], 'active_page')
            ->withQueryString();
    }

    public function filtersActive(Request $request): bool
    {
        return ListFilter::isActive($request, ['q', 'status']);
    }
}
