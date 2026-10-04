@extends('layouts.admin')

@section('title', 'Edit '.$booking->booking_number)

@section('content')
    @php
        $locationPicker = \App\Support\MapboxIntegration::isConfigured();
    @endphp

    <x-ui.page-header
        title="Edit {{ $booking->booking_number }}"
        subtitle="Main workspace: status, documents, trip details, and receipt."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
            @can('downloadReceipt', $booking)
                <x-ui.button href="{{ route('admin.bookings.receipt', $booking) }}" variant="secondary">
                    <x-ui.icon name="file-up" size="size-4" />
                    Download receipt
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            @include('bookings._edit_summary', ['booking' => $booking, 'thin' => false])
        </x-ui.card>

        <x-ui.card>
            @if ($locationPicker)
                <x-ui.section-heading
                    icon="map-pin"
                    title="Destination & route"
                    description="Search or click the map. Pickup and dropoff addresses below stay in sync."
                />
                <div class="mt-4">
                    @include('bookings._location_picker')
                </div>
            @else
                @include('bookings._route_map', [
                    'booking' => $booking,
                    'routeMap' => $routeMap,
                    'editMapSlot' => true,
                ])
            @endif
        </x-ui.card>
    </div>

    <div class="space-y-4">
        @include('bookings._workspace_status_gatepass', [
            'booking' => $booking,
            'statuses' => $statuses,
            'mode' => 'edit',
        ])

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <x-ui.card>
                <x-ui.section-heading icon="package" title="Documents" description="Delivery documents (EIR and POD)" />
                <div class="mt-3 space-y-4 text-sm">
                    @include('bookings._eir_pod_links', ['booking' => $booking])
                    @include('bookings._eir_pod_upload', [
                        'booking' => $booking,
                        'eirAction' => route('admin.bookings.eir.store', $booking),
                        'podAction' => route('admin.bookings.pod.store', $booking),
                        'redirectTo' => 'edit',
                    ])
                </div>
            </x-ui.card>

            <x-ui.card>
                @include('bookings._invoice', [
                    'booking' => $booking,
                    'markPaidAction' => route('admin.bookings.invoice.mark-paid', $booking),
                    'markUnpaidAction' => route('admin.bookings.invoice.mark-unpaid', $booking),
                ])
            </x-ui.card>
        </div>

        <x-ui.card>
            <x-ui.section-heading
                icon="package"
                title="Trip details"
                description="Update customer, driver, vehicle type, schedule, and addresses."
            />
            <div class="mt-4">
                @include('bookings._fields', [
                    'action' => route('admin.bookings.update', $booking),
                    'method' => 'PUT',
                    'booking' => $booking,
                    'customers' => $customers,
                    'drivers' => $drivers,
                    'pricings' => $pricings,
                    'showCustomer' => true,
                    'submitLabel' => 'Save trip details',
                    'routeMap' => $routeMap,
                    'suppressTripMap' => $locationPicker,
                ])
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="archive" title="Danger zone" />
            <div class="mt-4 flex flex-wrap gap-3">
                @can('cancel', $booking)
                    <div x-data="{ open: false }">
                        <form x-ref="cancelForm" method="post" action="{{ route('admin.bookings.cancel', $booking) }}">
                            @csrf
                            <x-ui.button type="button" variant="danger" @click="open = true">Cancel booking</x-ui.button>
                        </form>
                        <x-ui.confirm-dialog
                            title="Cancel this booking?"
                            description="The booking will be cancelled. This cannot be undone from the staff workflow."
                            confirm-label="Cancel booking"
                            cancel-label="Keep booking"
                            confirm-variant="danger"
                            form-ref="cancelForm"
                        />
                    </div>
                @endcan

                <div x-data="{ open: false }">
                    <form x-ref="archiveForm" method="post" action="{{ route('admin.bookings.destroy', $booking) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="ghost" class="!text-danger" @click="open = true">
                            <x-ui.icon name="archive" size="size-4" />
                            Archive booking
                        </x-ui.button>
                    </form>
                    <x-ui.confirm-dialog
                        title="Archive this booking?"
                        description="The booking will be soft-deleted and removed from the active list."
                        confirm-label="Archive booking"
                        cancel-label="Keep booking"
                        confirm-variant="danger"
                        form-ref="archiveForm"
                    />
                </div>
            </div>
        </x-ui.card>
    </div>
@endsection
