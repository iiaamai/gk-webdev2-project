@extends('layouts.driver')

@section('title', $booking->booking_number)

@section('content')
    @php
        $statusTone = match ($booking->status->value) {
            'in_transit' => 'info',
            'accepted' => 'success',
            'completed' => 'neutral',
            'cancelled' => 'danger',
            default => 'warning',
        };
    @endphp

    <x-ui.page-header :title="$booking->booking_number" subtitle="Delivery workspace">
        <x-slot:actions>
            <x-ui.button href="{{ route('driver.deliveries.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to deliveries
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge>
        <span class="text-sm text-text-muted">{{ $booking->vehicle_type }}</span>
    </div>

    <x-ui.card class="mb-6">
        <x-ui.section-heading icon="map-pin" title="Trip details" />
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-text-muted">Pickup</dt>
                <dd class="mt-1 font-medium text-text">{{ $booking->pickup_address }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Dropoff</dt>
                <dd class="mt-1 font-medium text-text">{{ $booking->dropoff_address }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Payout</dt>
                <dd class="mt-1 font-medium text-text">₱{{ number_format((float) $booking->payout, 2) }}</dd>
            </div>
            @if ($booking->cargo_desc)
                <div class="sm:col-span-2">
                    <dt class="text-text-muted">Cargo</dt>
                    <dd class="mt-1 text-text">{{ $booking->cargo_desc }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <div class="mb-6 space-y-4">
        @can('accept', $booking)
            <x-ui.card>
                <x-ui.section-heading icon="package" title="Accept delivery" />
                <form method="post" action="{{ route('driver.deliveries.accept', $booking) }}" class="mt-4" onsubmit="return confirm('Accept this delivery?');">
                    @csrf
                    <x-ui.button type="submit">
                        Accept delivery
                    </x-ui.button>
                </form>
            </x-ui.card>
        @endcan

        @can('updateDeliveryStatus', $booking)
            <x-ui.card>
                <x-ui.section-heading icon="activity" title="Update status" />
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($booking->status === \App\Enums\BookingStatus::Accepted)
                        <form method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="in_transit">
                            <x-ui.button type="submit">Mark in transit</x-ui.button>
                        </form>
                    @endif

                    @if ($booking->status === \App\Enums\BookingStatus::InTransit)
                        <form method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <x-ui.button type="submit">Mark completed</x-ui.button>
                        </form>
                        <p class="text-sm text-text-muted">Upload EIR and POD before completing.</p>
                    @endif
                </div>
            </x-ui.card>
        @endcan
    </div>

    @include('driver.deliveries._documents', ['booking' => $booking])

    <x-ui.card class="mb-6">
        @include('bookings._route_map', ['booking' => $booking, 'routeMap' => $routeMap])
    </x-ui.card>

    @can('downloadReceipt', $booking)
        <p class="mb-4 text-sm">
            <a href="{{ route('driver.deliveries.receipt', $booking) }}" class="font-medium text-primary hover:text-primary-shade-1">Download delivery receipt (PDF)</a>
        </p>
    @endcan
@endsection
