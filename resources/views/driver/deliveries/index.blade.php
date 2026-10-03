@extends('layouts.driver')

@section('title', 'Deliveries')

@section('content')
    @php
        $hasActiveDelivery = $active->isNotEmpty();
    @endphp

    <x-ui.page-header
        title="Deliveries"
        subtitle="Active trip and jobs matching {{ $driver->assignedVehicle?->pricing?->vehicle_type ?? 'your vehicle type' }}."
    />

    <section class="mb-8">
        <x-ui.section-heading icon="truck" title="Active delivery" />
        @if ($active->isEmpty())
            <p class="mt-4 text-sm text-text-muted">No active delivery. Accept a job below when you are ready.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($active as $booking)
                    <x-driver.job-card :booking="$booking" cta-label="Open" />
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <x-ui.section-heading
            icon="clipboard-list"
            title="Available jobs"
            description="Gatepass uploaded; matching your assigned vehicle type."
        />

        @if ($hasActiveDelivery)
            <div class="mt-4 rounded-lg border border-border bg-surface-inset px-4 py-3 text-sm text-text-muted" role="status">
                Finish your current delivery to accept new jobs. You can still view available jobs below.
            </div>
        @endif

        @if ($available->isEmpty())
            <p class="mt-4 text-sm text-text-muted">No available jobs right now. Check back later.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($available as $booking)
                    <x-driver.job-card
                        :booking="$booking"
                        cta-label="View"
                        :view-only="$hasActiveDelivery"
                    />
                @endforeach
            </div>
        @endif
    </section>
@endsection
