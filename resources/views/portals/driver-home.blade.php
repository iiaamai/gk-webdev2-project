@extends('layouts.driver')

@section('title', 'Overview')

@section('content')
    @php
        $hasActiveDelivery = $overview['active_booking'] !== null;
    @endphp

    <x-ui.page-header
        title="Driver overview"
        subtitle="Your trips for {{ $generatedAt->format('F j, Y') }} (Asia/Manila)."
    />

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-ui.card>
            <p class="text-sm text-text-muted">Available jobs</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['available_count'] }}</p>
            <p class="mt-1 text-xs text-text-muted">Matching your vehicle type with gatepass uploaded.</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-text-muted">Assigned vehicle</p>
            @if ($overview['plate_number'])
                <p class="mt-1 text-lg font-semibold text-text">{{ $overview['plate_number'] }}</p>
                <p class="text-sm text-text-muted">{{ $overview['vehicle_type'] }}</p>
            @else
                <p class="mt-1 text-sm text-text-muted">No fleet unit assigned yet.</p>
            @endif
        </x-ui.card>
    </div>

    <section class="mb-8">
        <x-ui.section-heading icon="package" title="Active delivery" description="One active trip at a time." />
        @if (! $hasActiveDelivery)
            <p class="mt-4 text-sm text-text-muted">No active delivery. Accept a job from the deliveries list.</p>
            <div class="mt-4">
                <x-ui.button href="{{ route('driver.deliveries.index') }}">
                    <x-ui.icon name="package" size="size-4" />
                    View deliveries
                </x-ui.button>
            </div>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-driver.job-card :booking="$overview['active_booking']" cta-label="Open" />
            </div>
        @endif
    </section>

    @if ($overview['available_count'] > 0)
        <section>
            <x-ui.section-heading icon="clipboard-list" title="Jobs waiting" />
            @if ($hasActiveDelivery)
                <div class="mt-4 rounded-lg border border-border bg-surface-inset px-4 py-3 text-sm text-text-muted" role="status">
                    Finish your current delivery to accept new jobs. {{ $overview['available_count'] }} job(s) are still listed for viewing.
                </div>
            @else
                <p class="mt-3 text-sm text-text-muted">{{ $overview['available_count'] }} job(s) ready to accept on the deliveries page.</p>
            @endif
            <div class="mt-4">
                <x-ui.button href="{{ route('driver.deliveries.index') }}" variant="secondary">
                    Browse available jobs
                </x-ui.button>
            </div>
        </section>
    @endif
@endsection
