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
        <a href="{{ route('customer.bookings.index') }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Pending</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['pending_count'] }}</p>
                <p class="mt-1 text-xs text-text-muted">Waiting for gatepass or a driver.</p>
            </x-ui.card>
        </a>
        <a href="{{ route('customer.bookings.index') }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">In progress</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['active_count'] }}</p>
                <p class="mt-1 text-xs text-text-muted">Accepted or in transit.</p>
            </x-ui.card>
        </a>
        <a href="{{ route('customer.bookings.index') }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Completed</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $overview['completed_count'] }}</p>
                <p class="mt-1 text-xs text-text-muted">Finished deliveries.</p>
            </x-ui.card>
        </a>
    </div>

    <section class="mb-8">
        <x-ui.section-heading icon="package" title="Active bookings" description="Pending and ongoing trips." />
        @if ($overview['active_bookings']->isEmpty())
            <div class="mt-4">
                <x-ui.empty-state title="No active bookings" icon="clipboard-list">
                    <x-slot:description>Create a booking to request a truck.</x-slot:description>
                    <x-slot:actions>
                        <x-ui.button href="{{ route('customer.bookings.create') }}" variant="secondary">
                            <x-ui.icon name="plus" size="size-4" />
                            Create a booking
                        </x-ui.button>
                    </x-slot:actions>
                </x-ui.empty-state>
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

    @if ($overview['unrated_completed']->isNotEmpty())
        <section class="mb-8">
            <x-ui.section-heading icon="star" title="Rate your trips" description="Share feedback on completed deliveries." />
            <ul class="mt-4 divide-y divide-border rounded-lg border border-border">
                @foreach ($overview['unrated_completed'] as $booking)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="font-medium text-text">{{ $booking->booking_number }}</p>
                            <p class="truncate text-sm text-text-muted">
                                {{ Str::limit($booking->pickup_address, 32) }} → {{ Str::limit($booking->dropoff_address, 32) }}
                            </p>
                        </div>
                        <x-ui.button href="{{ route('customer.bookings.show', $booking) }}" variant="secondary" class="!py-1.5 !text-xs">
                            Rate trip
                        </x-ui.button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
