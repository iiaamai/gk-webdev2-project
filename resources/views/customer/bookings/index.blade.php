@extends('layouts.customer')

@section('title', 'My Bookings')

@section('content')
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

    <div
        class="mb-6 flex gap-1 border-b border-border"
        role="tablist"
        aria-label="Booking lists"
    >
        <a
            href="{{ route('customer.bookings.index', ['tab' => 'active']) }}"
            role="tab"
            id="tab-active"
            aria-controls="panel-bookings"
            aria-selected="{{ $tab === 'active' ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                'border-primary text-text' => $tab === 'active',
                'border-transparent text-text-muted hover:text-text' => $tab !== 'active',
            ])
        >
            <x-ui.icon name="truck" size="size-4" />
            Active
        </a>
        <a
            href="{{ route('customer.bookings.index', ['tab' => 'history']) }}"
            role="tab"
            id="tab-history"
            aria-controls="panel-bookings"
            aria-selected="{{ $tab === 'history' ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                'border-primary text-text' => $tab === 'history',
                'border-transparent text-text-muted hover:text-text' => $tab !== 'history',
            ])
        >
            <x-ui.icon name="clipboard-list" size="size-4" />
            History
        </a>
    </div>

    <x-ui.list-filters
        :action="route('customer.bookings.index')"
        :clear-url="$clearUrl"
        :q="$search"
        search-placeholder="Booking number"
        :filters-active="$filtersActive"
        :status="$statusFilter"
        :status-options="$statusOptions"
        :payment="$tab === 'history' ? $paymentFilter : null"
        :payment-options="$tab === 'history' ? $paymentOptions : []"
        :tab="$tab"
    />

    <div id="panel-bookings" role="tabpanel" aria-labelledby="tab-{{ $tab }}">
        @if ($filtersActive && $bookings->total() === 0)
            <x-ui.list-no-results :clear-url="$clearUrl" />
        @elseif ($bookings->total() === 0)
            @if ($tab === 'history')
                <p class="text-sm text-text-muted">No completed or cancelled bookings yet.</p>
            @else
                <x-ui.empty-state title="No bookings yet" icon="clipboard-list">
                    <x-slot:description>Create your first booking from the pricing list.</x-slot:description>
                    <x-slot:actions>
                        <x-ui.button href="{{ route('customer.bookings.create') }}">
                            <x-ui.icon name="plus" size="size-4" />
                            New booking
                        </x-ui.button>
                    </x-slot:actions>
                </x-ui.empty-state>
            @endif
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($bookings as $booking)
                    <x-customer.booking-card :booking="$booking" />
                @endforeach
            </div>

            <x-ui.pagination :paginator="$bookings" />
        @endif
    </div>
@endsection