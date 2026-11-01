@php
    use App\Enums\BookingStatus;
    use App\Support\DriverDeliveryNextStep;

    $canComplete = $booking->eir !== null && $booking->pod !== null;
    $hint = DriverDeliveryNextStep::hint($booking);
    $hasNextStep = auth()->user()?->can('accept', $booking)
        || auth()->user()?->can('updateDeliveryStatus', $booking)
        || auth()->user()?->can('markArrivedAtPickup', $booking)
        || auth()->user()?->can('markRoundTripLapComplete', $booking)
        || auth()->user()?->can('markTransitCompleted', $booking);
@endphp

@if ($hasNextStep)
    <x-ui.card class="mb-6">
        <x-ui.section-heading icon="arrow-right-circle" title="Next step" :description="$hint" />

        <div class="mt-4 space-y-4">
            @can('accept', $booking)
                <div x-data="{ open: false }">
                    <form x-ref="acceptForm" method="post" action="{{ route('driver.deliveries.accept', $booking) }}">
                        @csrf
                        <x-ui.button type="button" @click="open = true">
                            Accept delivery
                        </x-ui.button>
                    </form>
                    <x-ui.confirm-dialog
                        title="Accept this delivery?"
                        description="You can only have one active delivery. The vehicle will be locked to this trip."
                        confirm-label="Accept delivery"
                        cancel-label="Cancel"
                        form-ref="acceptForm"
                    />
                </div>
            @endcan

            @can('updateDeliveryStatus', $booking)
                @if ($booking->status === BookingStatus::Accepted)
                    <div x-data="{ open: false }">
                        <form x-ref="transitToPickupForm" method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="transit_to_pickup">
                            <x-ui.button type="button" @click="open = true">Mark transit to pickup</x-ui.button>
                        </form>
                        <x-ui.confirm-dialog
                            title="Mark transit to pickup?"
                            description="The trip will move from accepted to en route to pickup."
                            confirm-label="Mark transit to pickup"
                            cancel-label="Cancel"
                            form-ref="transitToPickupForm"
                        />
                    </div>
                @elseif ($booking->status === BookingStatus::ArrivedAtPickup)
                    <div x-data="{ open: false }">
                        <form x-ref="haulForm" method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="in_transit">
                            <x-ui.button type="button" @click="open = true">Start haul in progress</x-ui.button>
                        </form>
                        <x-ui.confirm-dialog
                            title="Start haul in progress?"
                            description="Confirm you are leaving pickup with cargo (round trips / delivery leg)."
                            confirm-label="Start haul"
                            cancel-label="Cancel"
                            form-ref="haulForm"
                        />
                    </div>
                @elseif ($booking->status === BookingStatus::TransitCompleted)
                    <div class="flex flex-wrap gap-3">
                        <div x-data="{ open: false }">
                            <form x-ref="completeForm" method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                @if ($canComplete)
                                    <x-ui.button type="button" @click="open = true">
                                        Mark completed
                                    </x-ui.button>
                                @else
                                    <x-ui.button type="button" disabled>
                                        Mark completed
                                    </x-ui.button>
                                @endif
                            </form>
                            @if ($canComplete)
                                <x-ui.confirm-dialog
                                    title="Mark this delivery completed?"
                                    description="The trip will finish and the vehicle will be released."
                                    confirm-label="Mark completed"
                                    cancel-label="Cancel"
                                    form-ref="completeForm"
                                />
                            @endif
                        </div>
                        @unless ($canComplete)
                            <p class="basis-full text-sm text-warning">Upload EIR and POD in the documents section above before completing.</p>
                        @endunless
                    </div>
                @endif
            @endcan

            @can('markArrivedAtPickup', $booking)
                <form method="post" action="{{ route('driver.deliveries.arrived-at-pickup', $booking) }}">
                    @csrf
                    <x-ui.button type="submit">Arrived at pickup</x-ui.button>
                </form>
            @endcan

            @can('markRoundTripLapComplete', $booking)
                @php
                    $lapNext = \App\Support\BookingMultiTrip::nextLapNumber($booking);
                    $lapTotal = \App\Support\BookingMultiTrip::effectiveRoundTripsTotal($booking);
                @endphp
                <form method="post" action="{{ route('driver.deliveries.round-trip-lap.complete', $booking) }}">
                    @csrf
                    <x-ui.button type="submit">
                        Mark round trip complete ({{ $lapNext }} of {{ $lapTotal }})
                    </x-ui.button>
                </form>
            @endcan

            @can('markTransitCompleted', $booking)
                <div x-data="{ open: false }">
                    <form x-ref="transitCompletedForm" method="post" action="{{ route('driver.deliveries.transit-completed', $booking) }}">
                        @csrf
                        <x-ui.button type="button" @click="open = true">Mark transit completed</x-ui.button>
                    </form>
                    <x-ui.confirm-dialog
                        title="Mark transit completed?"
                        description="Confirm the haul leg is finished. You can then upload EIR and POD."
                        confirm-label="Mark transit completed"
                        cancel-label="Cancel"
                        form-ref="transitCompletedForm"
                    />
                </div>
            @endcan
        </div>
    </x-ui.card>
@endif
