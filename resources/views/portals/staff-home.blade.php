@extends('layouts.staff')

@section('title', 'Overview')

@section('content')
    @php
        $kpis = $overview['kpis'];
    @endphp

    <x-ui.page-header
        title="Staff overview"
        subtitle="Gatepass queue and active trips for {{ $generatedAt->format('F j, Y') }} (Asia/Manila)."
    />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <p class="text-sm text-text-muted">Pending (no gatepass)</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['pending_no_gatepass'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-text-muted">Ready for drivers</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['ready_for_drivers'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-text-muted">In transit</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['in_transit'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-text-muted">Unpaid invoices</p>
            <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['unpaid_invoices_count'] }}</p>
            <p class="mt-1 text-xs text-text-muted">₱{{ number_format((float) $kpis['unpaid_invoices_amount'], 2) }} total</p>
        </x-ui.card>
    </div>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <x-ui.section-heading
                icon="file-up"
                title="Needs gatepass"
                description="Pending bookings waiting for the first gatepass upload."
            />
            @if ($overview['needs_gatepass']->isEmpty())
                <p class="mt-4 text-sm text-text-muted">No bookings waiting for a gatepass.</p>
            @else
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($overview['needs_gatepass'] as $booking)
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-medium text-text">{{ $booking->booking_number }}</p>
                                <p class="truncate text-sm text-text-muted">{{ $booking->customer->name }}</p>
                                <p class="truncate text-xs text-text-subtle">
                                    {{ Str::limit($booking->pickup_address, 36) }} → {{ Str::limit($booking->dropoff_address, 36) }}
                                </p>
                            </div>
                            <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}" class="!py-1.5 !text-xs">
                                <x-ui.icon name="file-up" size="size-3.5" />
                                Upload
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="inbox" title="Needs attention" />
            @if (count($overview['attention_items']) === 0)
                <p class="mt-4 text-sm text-text-muted">Nothing flagged right now.</p>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($overview['attention_items'] as $item)
                        <li class="flex flex-col gap-1 rounded-md border border-border bg-surface-inset px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-text">{{ $item['label'] }}</p>
                                <p class="text-xs text-text-muted">{{ $item['booking']->booking_number }} · {{ $item['booking']->customer->name }}</p>
                            </div>
                            <x-ui.button href="{{ route('staff.bookings.edit', $item['booking']) }}" variant="ghost" class="!py-1.5 !text-xs">
                                Open
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card class="mb-6">
        <x-ui.section-heading
            icon="clipboard-list"
            title="Active bookings"
            description="In transit, accepted, or pending with gatepass."
        />
        @if ($overview['active_bookings']->isEmpty())
            <p class="mt-4 text-sm text-text-muted">No active trips right now.</p>
        @else
            <ul class="mt-4 divide-y divide-border">
                @foreach ($overview['active_bookings'] as $booking)
                    @php
                        $statusTone = match ($booking->status->value) {
                            'in_transit' => 'info',
                            'accepted' => 'success',
                            default => 'warning',
                        };
                    @endphp
                    <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-medium text-text">
                                <a href="{{ route('staff.bookings.show', $booking) }}" class="text-primary hover:text-primary-shade-1">
                                    {{ $booking->booking_number }}
                                </a>
                            </p>
                            <p class="truncate text-sm text-text-muted">{{ $booking->customer->name }}</p>
                            <p class="truncate text-xs text-text-subtle">
                                {{ Str::limit($booking->pickup_address, 40) }} → {{ Str::limit($booking->dropoff_address, 40) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge>
                            <span class="text-xs text-text-muted">{{ $booking->driver?->name ?? 'Unassigned' }}</span>
                            <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}" variant="ghost" class="!py-1.5 !text-xs">
                                Edit
                            </x-ui.button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="mt-4">
            <x-ui.button href="{{ route('staff.bookings.index') }}" variant="secondary">
                All bookings
            </x-ui.button>
        </div>
    </x-ui.card>
@endsection
