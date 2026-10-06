@extends('layouts.staff')

@section('title', $booking->booking_number)

@section('content')
    <x-ui.page-header title="{{ $booking->booking_number }}" subtitle="{{ $booking->customer->name }} · {{ $booking->customer->email }}">
        <x-slot:actions>
            <x-ui.button href="{{ route('staff.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
            @can('downloadReceipt', $booking)
                <x-ui.button href="{{ route('staff.bookings.receipt', $booking) }}" variant="secondary">
                    <x-ui.icon name="download" size="size-4" />
                    Download receipt
                </x-ui.button>
            @endcan
            @if (! $booking->hasGatepass())
                <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}">
                    <x-ui.icon name="file-up" size="size-4" />
                    Upload gatepass
                </x-ui.button>
            @endif
            <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}" variant="{{ $booking->hasGatepass() ? 'primary' : 'secondary' }}">
                <x-ui.icon name="pencil" size="size-4" />
                Edit workspace
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('bookings._trip_stepper', ['booking' => $booking])

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            @include('bookings._edit_summary', ['booking' => $booking, 'thin' => false])
            @cannot('update', $booking)
                <p class="mt-4 text-sm text-text-muted">Trip fields are locked after gatepass. Mark invoice paid here, or open Edit to manage gatepass.</p>
            @endcannot
        </x-ui.card>

        <x-ui.card>
            @include('bookings._route_map', [
                'booking' => $booking,
                'routeMap' => $routeMap,
            ])
        </x-ui.card>
    </div>

    <div class="space-y-4">
        @include('bookings._documents_panel', [
            'booking' => $booking,
            'cardClass' => '',
            'description' => 'Gatepass, EIR, and POD. Expand a row to preview.',
        ])

        <x-ui.card>
            @include('bookings._invoice', [
                'booking' => $booking,
                'markPaidAction' => route('staff.bookings.invoice.mark-paid', $booking),
                'redirectTo' => route('staff.bookings.show', $booking),
            ])
        </x-ui.card>

        <x-ui.card>
            @if ($booking->rating)
                @include('bookings._rating', ['booking' => $booking])
            @else
                <x-ui.section-heading icon="star" title="Trip rating" />
                <p class="mt-4 text-sm text-text-muted">No rating yet.</p>
            @endif
        </x-ui.card>
    </div>
@endsection
