@php
    $booking = $booking ?? null;
    $mode = $mode ?? ($booking ? 'edit' : 'create');
    $initialStatus = old('status', $booking?->status?->value ?? 'pending');
@endphp

<div
    class="grid gap-4 lg:grid-cols-2"
    x-data="{ bookingStatus: @js($initialStatus) }"
>
    <x-ui.card @class([
        'opacity-50 pointer-events-none' => $mode === 'create' ? false : false,
    ])>
        <x-ui.section-heading icon="activity" title="Update status" />
        @if ($mode === 'create')
            <div class="mt-4 space-y-4">
                <div>
                    <x-ui.label for="status">Status</x-ui.label>
                    <x-ui.select id="status" name="status" required x-model="bookingStatus">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.field-error name="status" />
                </div>
            </div>
        @elseif ($booking)
            <div class="mt-4 space-y-4" x-data="{ open: false }">
                <form x-ref="statusForm" method="post" action="{{ route('admin.bookings.status.update', $booking) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-ui.label for="status">Status</x-ui.label>
                        <x-ui.select id="status" name="status" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($booking->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <x-ui.button type="button" @click="open = true">Apply status</x-ui.button>
                </form>
                <x-ui.confirm-dialog
                    title="Apply this status change?"
                    description="Booking status will update immediately and may affect the trip workflow."
                    confirm-label="Apply status"
                    cancel-label="Cancel"
                    form-ref="statusForm"
                />
            </div>
        @endif
    </x-ui.card>

    @if ($mode === 'create')
        <x-ui.card x-bind:class="bookingStatus === 'pending' ? '' : 'opacity-50 pointer-events-none'">
            <x-ui.section-heading icon="file-up" title="Gatepass" />
            <span class="sr-only">Gatepass upload is available when status is pending.</span>
            <div class="mt-4 space-y-4">
                <div>
                    <x-ui.label for="gatepass">Gatepass image</x-ui.label>
                    <input
                        id="gatepass"
                        type="file"
                        name="gatepass"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                    >
                    <x-ui.field-error name="gatepass" />
                </div>
            </div>
        </x-ui.card>
    @elseif ($booking)
        @php
            $gatepassDisabled = $booking->status === \App\Enums\BookingStatus::Cancelled;
            $hasGatepass = $booking->hasGatepass();
        @endphp
        <x-ui.card @class([
            'opacity-50 pointer-events-none' => $gatepassDisabled,
        ])>
            <x-ui.section-heading icon="file-up" title="Gatepass" />
            <span class="sr-only">Gatepass unavailable when booking is cancelled.</span>
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
                <form x-ref="gatepassForm" method="post" action="{{ route('admin.bookings.gatepass.store', $booking) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <x-ui.label for="gatepass">Gatepass image</x-ui.label>
                        <input
                            id="gatepass"
                            type="file"
                            name="gatepass"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            required
                            @disabled($gatepassDisabled)
                            class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                            @change="onFileChange($event)"
                        >
                        @include('bookings._file_preview')
                        <x-ui.field-error name="gatepass" />
                    </div>
                    @if ($hasGatepass)
                        <x-ui.button type="button" :disabled="$gatepassDisabled" @click="open = true">
                            <x-ui.icon name="file-up" size="size-4" />
                            Replace gatepass
                        </x-ui.button>
                    @else
                        <x-ui.button type="submit" :disabled="$gatepassDisabled">
                            <x-ui.icon name="file-up" size="size-4" />
                            Upload gatepass
                        </x-ui.button>
                    @endif
                </form>
                @if ($hasGatepass)
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
    @endif
</div>
