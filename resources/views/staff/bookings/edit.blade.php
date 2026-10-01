@extends('layouts.staff')

@section('title', 'Edit '.$booking->booking_number)

@section('content')
    <x-ui.page-header
        title="Edit {{ $booking->booking_number }}"
        subtitle="Gatepass, invoice, and trip details (when unlocked)."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('staff.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            @include('bookings._edit_summary', ['booking' => $booking, 'thin' => true])
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
        @can('update', $booking)
            <x-ui.card>
                <x-ui.section-heading icon="package" title="Trip details" description="Update schedule, vehicle type, and addresses." />
                <div class="mt-4">
                    @include('bookings._fields', [
                        'action' => route('staff.bookings.update', $booking),
                        'method' => 'PUT',
                        'booking' => $booking,
                        'pricings' => $pricings,
                        'showCustomer' => false,
                        'submitLabel' => 'Save changes',
                        'routeMap' => $routeMap,
                    ])
                </div>
            </x-ui.card>
        @else
            <x-ui.card>
                <x-ui.section-heading icon="package" title="Trip details" />
                <p class="mt-3 text-sm text-text-muted">Trip fields are locked after gatepass upload.</p>
            </x-ui.card>
        @endcan

        @can('uploadGatepass', $booking)
            <x-ui.card>
                <x-ui.section-heading
                    icon="file-up"
                    title="{{ $booking->hasGatepass() ? 'Replace gatepass' : 'Upload gatepass' }}"
                    description="{{ $booking->hasGatepass() ? 'Replacing the gatepass keeps trip edits locked for staff.' : 'After the first upload, staff can no longer edit trip details.' }}"
                />
                <form method="post" action="{{ route('staff.bookings.gatepass.store', $booking) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    @if ($booking->hasGatepass())
                        <p class="text-sm text-text-muted">
                            Current file:
                            <a href="{{ route('documents.bookings.gatepass', $booking) }}" class="font-medium text-primary hover:text-primary-shade-1">Download gatepass</a>
                        </p>
                    @endif
                    <div>
                        <x-ui.label for="gatepass">Gatepass image</x-ui.label>
                        <input
                            id="gatepass"
                            type="file"
                            name="gatepass"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            required
                            class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                        >
                        <x-ui.field-error name="gatepass" />
                    </div>
                    <x-ui.button type="submit">
                        <x-ui.icon name="file-up" size="size-4" />
                        {{ $booking->hasGatepass() ? 'Replace gatepass' : 'Upload gatepass' }}
                    </x-ui.button>
                </form>
            </x-ui.card>
        @endcan

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <x-ui.card>
                <x-ui.section-heading icon="package" title="Documents" description="Delivery documents (EIR and POD)" />
                <div class="mt-3 text-sm">
                    @include('bookings._eir_pod_links', ['booking' => $booking])
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-heading icon="package" title="Invoice" />
                <div class="mt-3">
                    @include('bookings._invoice', [
                        'booking' => $booking,
                        'markPaidAction' => route('staff.bookings.invoice.mark-paid', $booking),
                    ])
                </div>
            </x-ui.card>
        </div>

        @can('cancel', $booking)
            <x-ui.card>
                <x-ui.section-heading icon="archive" title="Cancel" />
                <form method="post" action="{{ route('staff.bookings.cancel', $booking) }}" class="mt-4" onsubmit="return confirm('Cancel this booking?');">
                    @csrf
                    <x-ui.button type="submit" variant="danger">Cancel booking</x-ui.button>
                </form>
            </x-ui.card>
        @endcan
    </div>
@endsection
