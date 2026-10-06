@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
    @php
        $kpis = $overview['kpis'];
    @endphp

    <x-ui.page-header
        title="Admin overview"
        subtitle="Operations snapshot for {{ $generatedAt->format('F j, Y') }} (Asia/Manila)."
    />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <a href="{{ route('admin.bookings.index', ['scope' => 'pending_no_gatepass']) }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Pending (no gatepass)</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['pending_no_gatepass'] }}</p>
            </x-ui.card>
        </a>
        <a href="{{ route('admin.bookings.index', ['scope' => 'ready_for_drivers']) }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Ready for drivers</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['ready_for_drivers'] }}</p>
            </x-ui.card>
        </a>
        <a href="{{ route('admin.bookings.index', ['status' => 'in_transit']) }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">In transit</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['in_transit'] }}</p>
            </x-ui.card>
        </a>
        <a href="{{ route('admin.bookings.index', ['scope' => 'completed_month']) }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Completed this month</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['completed_this_month'] }}</p>
            </x-ui.card>
        </a>
        <a href="{{ route('admin.bookings.index', ['scope' => 'unpaid']) }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Unpaid invoices</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['unpaid_invoices_count'] }}</p>
                <p class="mt-1 text-xs text-text-muted">₱{{ number_format((float) $kpis['unpaid_invoices_amount'], 2) }} total</p>
            </x-ui.card>
        </a>
        <a href="{{ route('admin.fleet.index', ['status' => 'available']) }}" class="block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-ui.card class="h-full transition-colors hover:border-primary/40 hover:bg-primary-tone-1/40">
                <p class="text-sm text-text-muted">Fleet</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $kpis['fleet_available'] }} <span class="text-base font-normal text-text-muted">avail.</span></p>
                <p class="text-sm text-text-muted">{{ $kpis['fleet_in_use'] }} in use</p>
            </x-ui.card>
        </a>
    </div>

    <div class="mb-6 grid gap-4 lg:grid-cols-3 lg:items-stretch">
        <x-ui.card class="flex h-full flex-col lg:col-span-2">
            <x-ui.section-heading
                icon="map"
                title="Live operations map"
                description="Pickup-to-dropoff route for the selected active trip."
            />

            @if ($overview['active_bookings']->isEmpty())
                <div class="mt-4 flex flex-1 flex-col">
                    @include('bookings._map_placeholder', [
                        'routeMap' => null,
                        'mapSize' => 'overview',
                        'placeholderCaption' => 'No active trips to preview.',
                    ])
                </div>
            @else
                <div class="mt-4 flex shrink-0 flex-col gap-3 sm:flex-row sm:items-end">
                    <form method="get" action="{{ route('admin.home') }}" class="min-w-0 flex-1">
                        <x-ui.label for="map_booking">Active booking</x-ui.label>
                        <x-ui.select id="map_booking" name="map_booking" class="mt-1" onchange="this.form.submit()">
                            @foreach ($overview['active_bookings'] as $booking)
                                <option value="{{ $booking->id }}" @selected($selectedMapBookingId === $booking->id)>
                                    {{ $booking->booking_number }} · {{ $booking->status->label() }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </form>
                    @if ($mapBooking)
                        <x-ui.button
                            href="{{ route('admin.bookings.show', $mapBooking) }}"
                            variant="secondary"
                            class="flex w-full justify-center sm:w-auto"
                        >
                            <x-ui.icon name="eye" size="size-4" />
                            View booking
                        </x-ui.button>
                    @endif
                </div>

                @if ($mapBooking)
                    <div class="mt-4 flex min-h-0 flex-1 flex-col">
                        <p class="mb-3 text-sm text-text-muted">
                            <span class="font-medium text-text">{{ Str::limit($mapBooking->pickup_address, 48) }}</span>
                            →
                            <span class="font-medium text-text">{{ Str::limit($mapBooking->dropoff_address, 48) }}</span>
                            @if ($routeMap?->distanceKm !== null && $routeMap?->durationMinutes !== null)
                                <span class="text-text-subtle"> · {{ $routeMap->distanceKm }} km · {{ $routeMap->durationMinutes }} min</span>
                            @endif
                        </p>
                        @include('bookings._map_placeholder', [
                            'booking' => $mapBooking,
                            'routeMap' => $routeMap,
                            'mapSize' => 'overview',
                        ])
                    </div>
                @endif
            @endif
        </x-ui.card>

        <x-ui.card class="flex h-full flex-col lg:col-span-1">
            <x-ui.section-heading
                icon="clipboard-list"
                title="Active bookings"
                description="In transit, accepted, or pending with gatepass."
            />
            <div class="mt-4 flex min-h-0 flex-1 flex-col lg:min-h-[32rem]">
                @if ($overview['active_bookings']->isEmpty())
                    <p class="text-sm text-text-muted">No active trips right now.</p>
                @else
                    <ul class="min-h-0 flex-1 divide-y divide-border overflow-y-auto">
                        @foreach ($overview['active_bookings'] as $booking)
                            @php
                                $statusTone = $booking->status->badgeTone();
                            @endphp
                            <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="font-medium text-text">
                                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="text-primary hover:text-primary-shade-1">
                                            {{ $booking->booking_number }}
                                        </a>
                                    </p>
                                    <p class="truncate text-sm text-text-muted">{{ $booking->customer->name }}</p>
                                    <p class="truncate text-xs text-text-subtle">
                                        {{ Str::limit($booking->pickup_address, 40) }} → {{ Str::limit($booking->dropoff_address, 40) }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <x-ui.badge :tone="$statusTone">{{ $booking->status->label() }}</x-ui.badge>
                                    <span class="text-xs text-text-muted">{{ $booking->driver?->name ?? 'Unassigned' }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-4 shrink-0">
                    <x-ui.button href="{{ route('admin.bookings.index') }}" variant="secondary">
                        All bookings
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
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
                            <x-ui.button href="{{ route('admin.bookings.edit', $item['booking']) }}" variant="ghost" class="!py-1.5 !text-xs">
                                Open
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading
                icon="chart-column"
                title="Earnings snapshot"
                description="{{ $earningsSnapshot['period_label'] }}"
            />
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-text-muted">Completed trips</dt>
                    <dd class="text-lg font-semibold text-text">{{ $earningsSnapshot['kpis']['completed_trips'] }}</dd>
                </div>
                <div>
                    <dt class="text-text-muted">Completed revenue</dt>
                    <dd class="text-lg font-semibold text-text">₱{{ number_format((float) $earningsSnapshot['kpis']['completed_revenue'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-text-muted">Paid revenue</dt>
                    <dd class="text-lg font-semibold text-text">₱{{ number_format((float) $earningsSnapshot['kpis']['paid_revenue'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-text-muted">Avg. payout</dt>
                    <dd class="text-lg font-semibold text-text">₱{{ number_format((float) $earningsSnapshot['kpis']['average_completed_payout'], 2) }}</dd>
                </div>
            </dl>
            <div class="mt-4">
                <x-ui.button href="{{ route('admin.earnings.index') }}">
                    View full report
                </x-ui.button>
            </div>
        </x-ui.card>
    </div>

    <x-ui.card class="mb-6">
        <x-ui.section-heading icon="activity" title="Recent activity" />
        @if ($overview['recent_activity']->isEmpty())
            <p class="mt-4 text-sm text-text-muted">No activity logged yet.</p>
        @else
            <ul class="mt-4 divide-y divide-border text-sm">
                @foreach ($overview['recent_activity'] as $log)
                    <li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <x-ui.badge tone="neutral">{{ $log->action }}</x-ui.badge>
                            <p class="mt-1 text-text-muted">{{ $log->description }}</p>
                        </div>
                        <p class="shrink-0 text-xs text-text-subtle">
                            {{ $log->user?->name ?? 'System' }} · {{ $log->created_at?->timezone('Asia/Manila')->format('M j, g:i A') }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="mt-4">
            <x-ui.button href="{{ route('admin.activity-logs.index') }}" variant="secondary">
                All activity logs
            </x-ui.button>
        </div>
    </x-ui.card>
@endsection
