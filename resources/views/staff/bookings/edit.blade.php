@extends('layouts.staff')

@section('title', 'Edit '.$booking->booking_number)

@section('content')
    <x-ui.page-header
        title="Edit {{ $booking->booking_number }}"
        subtitle="Gatepass, invoice, and trip details (when unlocked)."
        class="sticky top-0 z-20 -mx-4 border-b border-border bg-surface/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('staff.bookings.show', $booking) }}" variant="secondary">
                <x-ui.icon name="eye" size="size-4" />
                View
            </x-ui.button>
            <x-ui.button href="{{ route('staff.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('bookings._trip_stepper', ['booking' => $booking])

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            @include('bookings._edit_summary', ['booking' => $booking, 'thin' => false])
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
                <x-ui.section-heading
                    icon="package"
                    title="Trip details"
                    description="Read-only after gatepass upload."
                />
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-text-muted">Pickup</dt>
                        <dd class="mt-1 font-medium text-text">{{ $booking->pickup_address }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Dropoff</dt>
                        <dd class="mt-1 font-medium text-text">{{ $booking->dropoff_address }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Preferred pickup</dt>
                        <dd class="mt-1 text-text">{{ $booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Vehicle type</dt>
                        <dd class="mt-1 text-text">{{ $booking->vehicle_type }}</dd>
                    </div>
                    @if ($booking->cargo_desc)
                        <div class="sm:col-span-2">
                            <dt class="text-text-muted">Cargo</dt>
                            <dd class="mt-1 text-text">{{ $booking->cargo_desc }}</dd>
                        </div>
                    @endif
                    @if ($booking->additional_requirements)
                        <div class="sm:col-span-2">
                            <dt class="text-text-muted">Requirements</dt>
                            <dd class="mt-1 text-text">{{ $booking->additional_requirements }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>
        @endcan

        @can('uploadGatepass', $booking)
            <x-ui.card>
                <x-ui.section-heading
                    icon="file-up"
                    title="{{ $booking->hasGatepass() ? 'Replace gatepass' : 'Upload gatepass' }}"
                    description="{{ $booking->hasGatepass() ? 'Replacing the gatepass keeps trip edits locked for staff.' : 'After the first upload, staff can no longer edit trip details.' }}"
                />
                <div
                    class="mt-4 space-y-4"
                    x-data="{
                        open: false,
                        previewUrl: null,
                        previewName: '',
                        onFileChange(event) {
                            const file = event.target.files?.[0];
                            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                            if (! file) { this.previewUrl = null; this.previewName = ''; return; }
                            this.previewUrl = URL.createObjectURL(file);
                            this.previewName = file.name;
                        },
                    }"
                >
                    <form x-ref="gatepassForm" method="post" action="{{ route('staff.bookings.gatepass.store', $booking) }}" enctype="multipart/form-data" class="space-y-4">
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
                                @change="onFileChange($event)"
                            >
                            @include('bookings._file_preview')
                            <x-ui.field-error name="gatepass" />
                        </div>
                        @if ($booking->hasGatepass())
                            <x-ui.button type="button" @click="open = true">
                                <x-ui.icon name="file-up" size="size-4" />
                                Replace gatepass
                            </x-ui.button>
                        @else
                            <x-ui.button type="submit">
                                <x-ui.icon name="file-up" size="size-4" />
                                Upload gatepass
                            </x-ui.button>
                        @endif
                    </form>
                    @if ($booking->hasGatepass())
                        <x-ui.confirm-dialog
                            title="Replace this gatepass?"
                            description="The current gatepass file will be overwritten."
                            confirm-label="Replace gatepass"
                            cancel-label="Cancel"
                            confirm-variant="danger"
                            form-ref="gatepassForm"
                        />
                    @endif
                </div>
            </x-ui.card>
        @endcan

        <div class="grid items-start gap-4 lg:grid-cols-2">
            @include('bookings._documents_panel', [
                'booking' => $booking,
                'cardClass' => '',
                'description' => 'Delivery documents (EIR and POD). Expand a row to preview.',
            ])

            <x-ui.card>
                @include('bookings._invoice', [
                    'booking' => $booking,
                    'markPaidAction' => route('staff.bookings.invoice.mark-paid', $booking),
                    'redirectTo' => route('staff.bookings.edit', $booking),
                ])
            </x-ui.card>
        </div>

        @can('cancel', $booking)
            <x-ui.card>
                <x-ui.section-heading icon="archive" title="Cancel" />
                <div class="mt-4" x-data="{ open: false }">
                    <form x-ref="cancelForm" method="post" action="{{ route('staff.bookings.cancel', $booking) }}">
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
            </x-ui.card>
        @endcan
    </div>
@endsection
