@php
    use App\Enums\BookingStatus;
    use App\Models\Pricing;
    use App\Models\Setting;
    use App\Support\BookingMultiTrip;

    $wrongTruckPercent = Setting::getValue('wrong_truck_fee_percent', '10');
    $pricings = $pricings ?? Pricing::query()->orderBy('vehicle_type')->get();
    $showPickupCapacityCard = in_array($booking->status, [
        BookingStatus::TransitToPickup,
        BookingStatus::ArrivedAtPickup,
        BookingStatus::InTransit,
    ], true)
        && (BookingMultiTrip::hasArrivedAtPickup($booking)
            || auth()->user()?->can('markArrivedAtPickup', $booking)
            || auth()->user()?->can('requestMultiTrip', $booking)
            || auth()->user()?->can('confirmMultiTrip', $booking)
            || auth()->user()?->can('changeVehicleType', $booking)
            || auth()->user()?->can('approveMultiTrip', $booking)
            || auth()->user()?->can('acceptMultiTrip', $booking));
@endphp

@if ($showPickupCapacityCard)
    <x-ui.card class="mb-6">
        @if ($booking->status === BookingStatus::TransitToPickup && auth()->user()?->isDriver())
            <p class="mb-4 text-sm text-text-muted">En route to pickup — confirm arrival in Next step when you reach the site.</p>
        @endif

        @if (BookingMultiTrip::hasArrivedAtPickup($booking))
        <x-ui.section-heading
            icon="repeat"
            title="Capacity at pickup"
            description="Round trips and vehicle type changes after the driver arrived at pickup."
        />

        <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-text-muted">Arrived at pickup</dt>
                <dd class="font-medium text-text">{{ $booking->arrived_at_pickup_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
            </div>
            @if ($booking->multi_trip_requested_rounds)
                <div>
                    <dt class="text-text-muted">Customer requested round trips</dt>
                    <dd class="font-medium text-text">{{ $booking->multi_trip_requested_rounds }}</dd>
                </div>
            @endif
            @if ($booking->multi_trip_office_rounds)
                <div>
                    <dt class="text-text-muted">Office approved round trips</dt>
                    <dd class="font-medium text-text">{{ $booking->multi_trip_office_rounds }}</dd>
                </div>
            @endif
            @if ($booking->multi_trip_office_status)
                <div>
                    <dt class="text-text-muted">Multi-trip status</dt>
                    <dd class="font-medium text-text">{{ $booking->multi_trip_office_status->label() }}</dd>
                </div>
            @endif
        </dl>

        @can('requestMultiTrip', $booking)
            <form method="post" action="{{ route('customer.bookings.multi-trip.request', $booking) }}" class="mt-6 space-y-3 border-t border-border pt-4">
                @csrf
                <x-ui.label for="round_trips">Estimated round trips</x-ui.label>
                <p class="text-xs text-text-muted">One round trip is pickup → dropoff → return to pickup. You can submit this once; office must approve and the driver must accept.</p>
                <x-ui.input id="round_trips" name="round_trips" type="number" min="1" max="99" value="{{ old('round_trips', 2) }}" required />
                <x-ui.field-error name="round_trips" />
                <x-ui.button type="submit">Submit round-trip request</x-ui.button>
            </form>
        @endcan

        @can('confirmMultiTrip', $booking)
            <div class="mt-6 border-t border-border pt-4">
                <p class="text-sm text-text">
                    Office approved <strong>{{ $booking->multi_trip_office_rounds }}</strong> round trip(s), different from your estimate of {{ $booking->multi_trip_requested_rounds }}.
                </p>
                <form method="post" action="{{ route('customer.bookings.multi-trip.confirm', $booking) }}" class="mt-3">
                    @csrf
                    <x-ui.button type="submit">Confirm revised round trips</x-ui.button>
                </form>
            </div>
        @endcan

        @can('changeVehicleType', $booking)
            <form method="post" action="{{ route('customer.bookings.vehicle-type.change', $booking) }}" class="mt-6 space-y-3 border-t border-border pt-4">
                @csrf
                <x-ui.label for="pricing_id">Change vehicle type (once)</x-ui.label>
                <p class="text-xs text-text-muted">A {{ $wrongTruckPercent }}% wrong-truck fee on the original list fare will be added to your invoice.</p>
                <select id="pricing_id" name="pricing_id" class="mt-1 w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm" required>
                    <option value="">Select type</option>
                    @foreach ($pricings as $pricing)
                        @if ((int) $pricing->id !== (int) $booking->pricing_id)
                            <option value="{{ $pricing->id }}" @selected(old('pricing_id') == $pricing->id)>
                                {{ $pricing->vehicle_type }} — ₱{{ number_format((float) $pricing->amount, 2) }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <x-ui.field-error name="pricing_id" />
                <x-ui.button type="submit" variant="secondary">Change vehicle type</x-ui.button>
            </form>
        @endcan

        @if ($booking->vehicle_type_changed_at)
            <p class="mt-4 text-sm text-text-muted">Vehicle type was changed on {{ $booking->vehicle_type_changed_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}.</p>
        @endif

        @isset($officeRoutePrefix)
        @can('approveMultiTrip', $booking)
            <div class="mt-6 border-t border-border pt-4">
                <p class="text-sm font-medium text-text">Pending multi-trip request: {{ $booking->multi_trip_requested_rounds }} round trip(s)</p>
                <form method="post" action="{{ route($officeRoutePrefix.'.bookings.multi-trip.approve', $booking) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-ui.label for="office_round_trips">Approved round trips</x-ui.label>
                        <x-ui.input id="office_round_trips" name="round_trips" type="number" min="1" max="99" value="{{ old('round_trips', $booking->multi_trip_requested_rounds) }}" required />
                    </div>
                    <x-ui.button type="submit">Approve</x-ui.button>
                </form>
                <div class="mt-3" x-data="{ open: false }">
                    <form x-ref="rejectForm" method="post" action="{{ route($officeRoutePrefix.'.bookings.multi-trip.reject', $booking) }}">
                        @csrf
                        <x-ui.button type="button" variant="danger" @click="open = true">Reject (cancel booking)</x-ui.button>
                    </form>
                    <x-ui.confirm-dialog
                        title="Reject multi-trip request?"
                        description="This will cancel the booking. The customer must create a new booking if they still need service."
                        confirm-label="Reject and cancel"
                        cancel-label="Keep booking"
                        confirm-variant="danger"
                        form-ref="rejectForm"
                    />
                </div>
                <x-ui.field-error name="round_trips" />
            </div>
        @endcan
        @endisset

        @can('acceptMultiTrip', $booking)
            <div class="mt-6 border-t border-border pt-4">
                <p class="text-sm text-text">Office approved <strong>{{ $booking->multi_trip_office_rounds }}</strong> round trip(s). Accept before completing the delivery.</p>
                <form method="post" action="{{ route('driver.deliveries.multi-trip.accept', $booking) }}" class="mt-3">
                    @csrf
                    <x-ui.button type="submit">Accept round-trip count</x-ui.button>
                </form>
            </div>
        @endcan

        @endif

        @if (BookingMultiTrip::multiTripBlocksCompletion($booking) && auth()->user()?->isDriver())
            <p class="mt-4 text-sm text-warning">Resolve the round-trip request before marking transit completed.</p>
        @endif
    </x-ui.card>
@endif
