@extends('layouts.customer')

@section('title', $booking->booking_number)

@section('content')
    @php
        $statusTone = match ($booking->status->value) {
            'pending' => 'warning',
            'accepted', 'in_transit' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'neutral',
        };
        $showDriverContact = $booking->driver_id
            && in_array($booking->status, [
                \App\Enums\BookingStatus::Accepted,
                \App\Enums\BookingStatus::InTransit,
                \App\Enums\BookingStatus::Completed,
            ], true);
        $driver = $booking->driver;
        $canViewEir = auth()->user()?->can('viewEir', $booking) ?? false;
        $canViewPod = auth()->user()?->can('viewPod', $booking) ?? false;
    @endphp

    <x-ui.page-header :title="$booking->booking_number" subtitle="Booking details">
        <x-slot:actions>
            <x-ui.button href="{{ route('customer.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge>
        <span class="text-sm text-text-muted">{{ $booking->vehicle_type }}</span>
        <span class="text-sm font-medium text-text">₱{{ number_format((float) $booking->payout, 2) }}</span>
    </div>

    @if ($showDriverContact && $driver)
        <x-ui.card class="mb-6">
            <x-ui.section-heading icon="user" title="Your driver" description="Contact details for this delivery." />
            <div class="mt-4 flex items-start gap-4">
                <x-ui.user-avatar :user="$driver" size="lg" />
                <dl class="grid min-w-0 flex-1 grid-cols-[6.5rem_1fr] gap-x-3 gap-y-2 text-sm">
                    <dt class="text-text-muted">Name</dt>
                    <dd class="font-medium text-text">{{ $driver->name }}</dd>
                    <dt class="text-text-muted">Mobile</dt>
                    <dd class="text-text">
                        @if (filled($driver->mobile))
                            <a href="tel:{{ $driver->mobile }}" class="font-medium text-primary hover:text-primary-shade-1">{{ $driver->mobile }}</a>
                        @else
                            <span class="text-text-muted">Not provided</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </x-ui.card>
    @elseif (! $booking->driver_id)
        <x-ui.card class="mb-6">
            <x-ui.section-heading icon="user" title="Driver" />
            <p class="mt-4 text-sm text-text-muted">Waiting for gatepass and driver assignment.</p>
        </x-ui.card>
    @endif

    @if ($booking->driver_id)
        <x-ui.card class="mb-6">
            @include('bookings._route_map', ['booking' => $booking, 'routeMap' => $routeMap])
        </x-ui.card>
    @endif

    @if ($canViewEir || $canViewPod)
        <x-ui.card class="mb-6">
            <x-ui.section-heading
                icon="file"
                title="Documents"
                description="EIR and POD when available for this status. Gatepass is not shown to customers."
            />
            <div class="mt-4 space-y-3 text-sm">
                @include('bookings._eir_pod_links', ['booking' => $booking])
            </div>
        </x-ui.card>
    @endif

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
            @if (filled($booking->pickup_port_number))
                <div>
                    <dt class="text-text-muted">Port number</dt>
                    <dd class="mt-1 text-text">{{ $booking->pickup_port_number }}</dd>
                </div>
            @endif
            @if (filled($booking->pickup_container_number))
                <div>
                    <dt class="text-text-muted">Container number</dt>
                    <dd class="mt-1 text-text">{{ $booking->pickup_container_number }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-text-muted">Preferred pickup</dt>
                <dd class="mt-1 text-text">{{ $booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Amount</dt>
                <dd class="mt-1 font-medium text-text">₱{{ number_format((float) $booking->payout, 2) }}</dd>
            </div>
            @if ($booking->cargo_desc)
                <div class="sm:col-span-2">
                    <dt class="text-text-muted">Cargo</dt>
                    <dd class="mt-1 text-text">{{ $booking->cargo_desc }}</dd>
                </div>
            @endif
            @if ($booking->additional_requirements)
                <div class="sm:col-span-2">
                    <dt class="text-text-muted">Requirements</dt>
                    <dd class="mt-1 text-text">{{ $booking->additional_requirements }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    @if ($booking->invoice)
        <x-ui.card class="mb-6">
            @include('bookings._invoice', ['booking' => $booking])
        </x-ui.card>
    @endif

    @if ($booking->rating || (auth()->user()?->can('create', [\App\Models\Rating::class, $booking])))
        <x-ui.card class="mb-6">
            @include('bookings._rating', [
                'booking' => $booking,
                'canRate' => auth()->user()?->can('create', [\App\Models\Rating::class, $booking]),
            ])
        </x-ui.card>
    @endif
@endsection
