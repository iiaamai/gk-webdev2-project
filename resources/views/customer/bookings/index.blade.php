@extends('layouts.customer')

@section('title', 'My Bookings')

@section('content')
    @php
        $bothEmpty = $active->isEmpty() && $history->isEmpty();
    @endphp

    <x-ui.page-header
        title="My Bookings"
        subtitle="Active trips and history for your account."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('customer.bookings.create') }}">
                <x-ui.icon name="plus" size="size-4" />
                New booking
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-data="{ tab: 'active' }">
        <div
            class="mb-6 flex gap-1 border-b border-border"
            role="tablist"
            aria-label="Booking lists"
        >
            <button
                type="button"
                role="tab"
                id="tab-active"
                aria-controls="panel-active"
                @click="tab = 'active'"
                :aria-selected="tab === 'active'"
                :class="tab === 'active'
                    ? 'border-primary text-text'
                    : 'border-transparent text-text-muted hover:text-text'"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
            >
                <x-ui.icon name="truck" size="size-4" />
                Active
            </button>
            <button
                type="button"
                role="tab"
                id="tab-history"
                aria-controls="panel-history"
                @click="tab = 'history'"
                :aria-selected="tab === 'history'"
                :class="tab === 'history'
                    ? 'border-primary text-text'
                    : 'border-transparent text-text-muted hover:text-text'"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
            >
                <x-ui.icon name="clipboard-list" size="size-4" />
                History
            </button>
        </div>

        <div
            id="panel-active"
            role="tabpanel"
            aria-labelledby="tab-active"
            x-show="tab === 'active'"
            x-cloak
        >
            @if ($bothEmpty)
                <x-ui.empty-state title="No bookings yet" icon="clipboard-list">
                    <x-slot:description>Create your first booking from the pricing list.</x-slot:description>
                    <x-slot:actions>
                        <x-ui.button href="{{ route('customer.bookings.create') }}">
                            <x-ui.icon name="plus" size="size-4" />
                            New booking
                        </x-ui.button>
                    </x-slot:actions>
                </x-ui.empty-state>
            @elseif ($active->isEmpty())
                <p class="text-sm text-text-muted">No active bookings. Start a new trip when you are ready.</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($active as $booking)
                        <x-customer.booking-card :booking="$booking" />
                    @endforeach
                </div>
            @endif
        </div>

        <div
            id="panel-history"
            role="tabpanel"
            aria-labelledby="tab-history"
            x-show="tab === 'history'"
            x-cloak
        >
            @if ($bothEmpty)
                <x-ui.empty-state title="No bookings yet" icon="clipboard-list">
                    <x-slot:description>Create your first booking from the pricing list.</x-slot:description>
                    <x-slot:actions>
                        <x-ui.button href="{{ route('customer.bookings.create') }}">
                            <x-ui.icon name="plus" size="size-4" />
                            New booking
                        </x-ui.button>
                    </x-slot:actions>
                </x-ui.empty-state>
            @elseif ($history->isEmpty())
                <p class="text-sm text-text-muted">No completed or cancelled bookings yet.</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($history as $booking)
                        <x-customer.booking-card :booking="$booking" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
