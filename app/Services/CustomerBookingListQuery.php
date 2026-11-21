<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\User;
use App\Support\ListFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CustomerBookingListQuery
{
    public const string TAB_ACTIVE = 'active';

    public const string TAB_HISTORY = 'history';

    public function resolveTab(Request $request): string
    {
        $tab = (string) $request->query('tab', self::TAB_ACTIVE);

        return in_array($tab, [self::TAB_ACTIVE, self::TAB_HISTORY], true)
            ? $tab
            : self::TAB_ACTIVE;
    }

    /**
     * @return array<string, string>
     */
    public function statusOptionsForTab(string $tab): array
    {
        $statuses = $tab === self::TAB_HISTORY
            ? [BookingStatus::Completed, BookingStatus::Cancelled]
            : [BookingStatus::Pending, ...BookingStatus::activeDeliveryStatuses()];

        $options = [];
        foreach ($statuses as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function paymentOptions(): array
    {
        return [
            InvoiceStatus::Paid->value => InvoiceStatus::Paid->label(),
            InvoiceStatus::Unpaid->value => InvoiceStatus::Unpaid->label(),
        ];
    }

    public function paginate(User $customer, Request $request): LengthAwarePaginator
    {
        $tab = $this->resolveTab($request);
        $search = ListFilter::searchTerm($request);
        $status = BookingStatus::tryFrom((string) $request->query('status', ''));
        $payment = (string) $request->query('payment', '');
        $allowedStatuses = array_keys($this->statusOptionsForTab($tab));

        if ($status !== null && ! in_array($status->value, $allowedStatuses, true)) {
            $status = null;
        }

        $query = Booking::query()
            ->where('customer_id', $customer->id)
            ->with(['pricing', 'vehicle.pricing', 'invoice']);

        if ($tab === self::TAB_HISTORY) {
            $query->whereIn('status', [BookingStatus::Completed, BookingStatus::Cancelled]);
        } else {
            $query->where(function ($inner): void {
                $inner->where('status', BookingStatus::Pending)
                    ->orWhereIn('status', BookingStatus::activeDeliveryStatuses());
            });
        }

        if ($search !== '') {
            $query->where('booking_number', 'like', '%'.$search.'%');
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($tab === self::TAB_HISTORY && in_array($payment, [InvoiceStatus::Paid->value, InvoiceStatus::Unpaid->value], true)) {
            $invoiceStatus = InvoiceStatus::from($payment);
            $query->whereHas('invoice', function ($invoice) use ($invoiceStatus): void {
                $invoice->where('status', $invoiceStatus);
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();
    }

    public function filtersActive(Request $request, string $tab): bool
    {
        $keys = ['q', 'status'];

        if ($tab === self::TAB_HISTORY) {
            $keys[] = 'payment';
        }

        return ListFilter::isActive($request, $keys);
    }
}
