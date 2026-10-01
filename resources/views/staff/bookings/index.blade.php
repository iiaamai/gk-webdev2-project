@extends('layouts.staff')

@section('title', 'Bookings')

@section('content')
    @php
        use App\Enums\BookingStatus;
        $statusOptions = collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $status) => [$status->value => $status->value])->all();
    @endphp

    <x-ui.page-header title="Bookings" subtitle="Update trips, upload gatepass, and manage invoices." />

    <x-ui.list-filters
        :action="route('staff.bookings.index')"
        :clear-url="route('staff.bookings.index')"
        :q="$search"
        search-placeholder="Booking number, customer name or email"
        :filters-active="$filtersActive"
        :status="$statusFilter"
        :status-options="$statusOptions"
    />

    @if ($bookings->total() === 0)
        @if ($filtersActive)
            <x-ui.list-no-results :clear-url="route('staff.bookings.index')" />
        @else
            <x-ui.empty-state title="No bookings yet" icon="clipboard-list">
                <x-slot:description>Customer and admin bookings will appear here for gatepass and updates.</x-slot:description>
            </x-ui.empty-state>
        @endif
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Number</th>
                    <th class="px-4 py-3 font-medium">Customer</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Vehicle type</th>
                    <th class="px-4 py-3 font-medium">Gatepass</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($bookings as $booking)
                @php
                    $statusTone = match ($booking->status->value) {
                        'pending' => 'warning',
                        'accepted', 'in_transit' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'neutral',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $booking->booking_number }}</td>
                    <td class="px-4 py-3">{{ $booking->customer->name }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3">{{ $booking->vehicle_type }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge :tone="$booking->hasGatepass() ? 'success' : 'neutral'">
                            {{ $booking->hasGatepass() ? 'Yes' : 'No' }}
                        </x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-ui.button href="{{ route('staff.bookings.show', $booking) }}" variant="secondary" class="!py-1.5 !text-xs">
                                View
                            </x-ui.button>
                            <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}" variant="ghost" class="!py-1.5 !text-xs">
                                <x-ui.icon name="pencil" size="size-3.5" />
                                Edit
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$bookings" />
    @endif
@endsection
