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
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.field-error name="status" />
                </div>
            </div>
        @elseif ($booking)
            <form method="post" action="{{ route('admin.bookings.status.update', $booking) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <x-ui.label for="status">Status</x-ui.label>
                    <x-ui.select id="status" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($booking->status === $status)>{{ $status->value }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <x-ui.button type="submit">Apply status</x-ui.button>
            </form>
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
        @endphp
        <x-ui.card @class([
            'opacity-50 pointer-events-none' => $gatepassDisabled,
        ])>
            <x-ui.section-heading icon="file-up" title="Gatepass" />
            <span class="sr-only">Gatepass unavailable when booking is cancelled.</span>
            <form method="post" action="{{ route('admin.bookings.gatepass.store', $booking) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
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
                    >
                    <x-ui.field-error name="gatepass" />
                </div>
                <x-ui.button type="submit" :disabled="$gatepassDisabled">
                    <x-ui.icon name="file-up" size="size-4" />
                    {{ $booking->hasGatepass() ? 'Replace gatepass' : 'Upload gatepass' }}
                </x-ui.button>
            </form>
        </x-ui.card>
    @endif
</div>
