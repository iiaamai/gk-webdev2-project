@extends('layouts.driver')

@section('title', 'Deliveries')

@section('content')
    @php
        $hasActiveDelivery = $active->total() > 0;
    @endphp

    <x-ui.page-header
        title="Deliveries"
        subtitle="Active trip and jobs matching {{ $driver->assignedVehicle?->pricing?->vehicle_type ?? 'your vehicle type' }}."
    />

    <x-ui.list-filters
        :action="route('driver.deliveries.index')"
        :clear-url="$clearUrl"
        :q="$search"
        search-placeholder="Booking number"
        :filters-active="$filtersActive"
        :status="$statusFilter"
        :status-options="$statusOptions"
    />

    @if ($filtersActive && $active->total() === 0 && $available->total() === 0 && ! ($cancelledBooking ?? null))
        <x-ui.list-no-results :clear-url="$clearUrl" />
    @else
    <section class="mb-8">
        <x-ui.section-heading icon="truck" title="Active delivery" />
        @if ($cancelledBooking ?? null)
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-driver.job-card :booking="$cancelledBooking" cta-label="Back to dashboard" />
            </div>
        @elseif ($active->total() === 0)
            <p class="mt-4 text-sm text-text-muted">No active delivery. Accept a job below when you are ready.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($active as $booking)
                    <x-driver.job-card :booking="$booking" cta-label="Open" show-next-step-hint />
                @endforeach
            </div>
            <x-ui.pagination :paginator="$active" />
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

        @if ($available->total() === 0)
            <p class="mt-4 text-sm text-text-muted">No available jobs right now. Check back later.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($available as $booking)
                    <x-driver.job-card
                        :booking="$booking"
                        :cta-label="$hasActiveDelivery ? 'View' : 'Accept'"
                        :view-only="$hasActiveDelivery"
                    />
                @endforeach
            </div>
            <x-ui.pagination :paginator="$available" />
        @endif
    </section>
    @endif
@endsection