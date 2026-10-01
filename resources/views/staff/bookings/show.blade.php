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
                    <x-ui.icon name="file-up" size="size-4" />
                    Download receipt
                </x-ui.button>
            @endcan
            <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}">
                <x-ui.icon name="pencil" size="size-4" />
                Edit workspace
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            @include('bookings._edit_summary', ['booking' => $booking, 'thin' => true])
            @cannot('update', $booking)
                <p class="mt-4 text-sm text-text-muted"><em>Trip fields are locked after gatepass. Use Edit to replace gatepass or mark invoice paid.</em></p>
            @endcannot
        </x-ui.card>

        <x-ui.card>
            @include('bookings._route_map', [
                'booking' => $booking,
                'routeMap' => $routeMap,
                'editMapSlot' => true,
            ])
        </x-ui.card>
    </div>

    <div class="space-y-4">
        <x-ui.card>
            <x-ui.section-heading icon="file-up" title="Gatepass" />
            <div class="mt-3 space-y-3 text-sm">
                @if ($booking->hasGatepass())
                    <a href="{{ route('documents.bookings.gatepass', $booking) }}" class="font-medium text-primary hover:text-primary-shade-1">Download gatepass</a>
                @else
                    <p class="text-text-muted">Not uploaded yet.</p>
                    @can('uploadGatepass', $booking)
                        <x-ui.button href="{{ route('staff.bookings.edit', $booking) }}">
                            <x-ui.icon name="file-up" size="size-4" />
                            Upload gatepass
                        </x-ui.button>
                    @endcan
                @endif
            </div>
        </x-ui.card>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <x-ui.card>
                <x-ui.section-heading icon="package" title="Documents" description="Delivery documents (EIR and POD)" />
                <div class="mt-3 text-sm">
                    @include('bookings._eir_pod_links', ['booking' => $booking])
                </div>
            </x-ui.card>

            <x-ui.card>
                @include('bookings._invoice', ['booking' => $booking])
            </x-ui.card>
        </div>
    </div>
@endsection
