<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
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
        $scope = (string) $request->query('scope', '');
        $phase = (string) $request->query('phase', '');

        $monthStart = now('Asia/Manila')->copy()->startOfMonth();
        $monthEnd = now('Asia/Manila')->copy()->endOfMonth();

        return Booking::query()
            ->with(['customer', 'driver', 'pricing', 'vehicle.pricing', 'invoice'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('booking_number', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function ($customer) use ($search): void {
                            $customer->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($phase === 'to_pickup', fn ($query) => $query->where('status', BookingStatus::TransitToPickup))
            ->when($phase === 'on_trip', fn ($query) => $query->whereIn('status', BookingStatus::onTripStatuses()))
            ->when($status !== null && $scope === '' && $phase === '', fn ($query) => $query->where('status', $status))
            ->when($scope === 'pending_no_gatepass', function ($query): void {
                $query->where('status', BookingStatus::Pending)
                    ->where(function ($inner): void {
                        $inner->whereNull('gatepass_path')->orWhere('gatepass_path', '');
                    });
            })
            ->when($scope === 'ready_for_drivers', function ($query): void {
                $query->where('status', BookingStatus::Pending)
                    ->whereNotNull('gatepass_path')
                    ->where('gatepass_path', '!=', '')
                    ->whereNull('driver_id')
                    ->where('is_locked', false);
            })
            ->when($scope === 'unpaid', function ($query): void {
                $query->whereHas('invoice', function ($invoice): void {
                    $invoice->where('status', InvoiceStatus::Unpaid);
                });
            })
            ->when($scope === 'pending_payment_proofs', function ($query): void {
                $query->whereHas('invoice', function ($invoice): void {
                    $invoice->pendingPaymentProof();
                });
            })
            ->when($scope === 'completed_month', function ($query) use ($monthStart, $monthEnd): void {
                $query->where('status', BookingStatus::Completed)
                    ->whereRaw('COALESCE(accepted_at, created_at) >= ?', [$monthStart])
                    ->whereRaw('COALESCE(accepted_at, created_at) <= ?', [$monthEnd]);
            })
            ->orderByDesc('created_at')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();
    }
}
