@extends('layouts.customer')

@section('title', 'Overview')

@section('content')
    <x-ui.page-header
        title="Customer overview"
        subtitle="Your trips for {{ $generatedAt->format('F j, Y') }} (Asia/Manila)."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('customer.bookings.create') }}">
                <x-ui.icon name="plus" size="size-4" />
                New booking
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.card>
            <p class="text-sm text-text-muted">Pending</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['pending_count'] }}</p>
            <p class="mt-1 text-xs text-text-muted">Waiting for gatepass or a driver.</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-text-muted">In progress</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['active_count'] }}</p>
            <p class="mt-1 text-xs text-text-muted">Accepted or in transit.</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-text-muted">Completed</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['completed_count'] }}</p>
            <p class="mt-1 text-xs text-text-muted">Finished deliveries.</p>
        </x-ui.card>
    </div>

    <section class="mb-8">
        <x-ui.section-heading icon="package" title="Active bookings" description="Pending and ongoing trips." />
        @if ($overview['active_bookings']->isEmpty())
            <p class="mt-4 text-sm text-text-muted">No active bookings right now.</p>
            <div class="mt-4">
                <x-ui.button href="{{ route('customer.bookings.create') }}" variant="secondary">
                    <x-ui.icon name="plus" size="size-4" />
                    Create a booking
                </x-ui.button>
            </div>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($overview['active_bookings'] as $booking)
                    <x-customer.booking-card :booking="$booking" />
                @endforeach
            </div>
            <div class="mt-4">
                <x-ui.button href="{{ route('customer.bookings.index') }}" variant="ghost">
                    View all bookings
                </x-ui.button>
            </div>
        @endif
    </section>
@endsection
