@php
    $hasGatepass = $booking->hasGatepass();
    $hasEir = $booking->eir !== null;
    $hasPod = $booking->pod !== null;
    $canViewGatepass = auth()->user()?->can('viewGatepass', $booking) ?? false;
    $canViewEir = auth()->user()?->can('viewEir', $booking) ?? false;
    $canViewPod = auth()->user()?->can('viewPod', $booking) ?? false;
    $canUploadEir = auth()->user()?->can('uploadEir', $booking) ?? false;
    $canUploadPod = auth()->user()?->can('uploadPod', $booking) ?? false;
@endphp

@if ($canViewGatepass || $canViewEir || $canViewPod || $canUploadEir || $canUploadPod)
    <x-ui.card class="mb-6" x-data="{ open: null }">
        <x-ui.section-heading
            icon="file"
            title="Documents"
            description="Gatepass, EIR, and POD. Expand a row to preview or upload."
        />

        <div class="mt-4 divide-y divide-border rounded-lg border border-border">
            @if ($canViewGatepass)
                <div class="bg-surface-elevated">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-surface-inset"
                        @click="open = open === 'gatepass' ? null : 'gatepass'"
                        :aria-expanded="open === 'gatepass'"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-text">Gatepass</p>
                            <p class="text-xs text-text-muted">
                                {{ $hasGatepass ? 'Uploaded — tap to preview' : 'Not uploaded' }}
                            </p>
                        </div>
                        @if ($hasGatepass)
                            <img
                                src="{{ route('documents.bookings.gatepass', $booking) }}"
                                alt=""
                                class="h-12 w-12 shrink-0 rounded-md border border-border object-cover"
                            >
                        @else
                            <x-ui.badge tone="warning">Missing</x-ui.badge>
                        @endif
                        <x-ui.icon
                            name="chevron-down"
                            size="size-4"
                            class="shrink-0 text-text-muted transition-transform"
                            x-bind:class="open === 'gatepass' && 'rotate-180'"
                        />
                    </button>
                    <div x-show="open === 'gatepass'" x-cloak class="border-t border-border px-4 py-4">
                        @if ($hasGatepass)
                            <img
                                src="{{ route('documents.bookings.gatepass', $booking) }}"
                                alt="Gatepass for {{ $booking->booking_number }}"
                                class="max-h-96 w-full rounded-md border border-border object-contain"
                            >
                            <p class="mt-3 text-sm">
                                <a
                                    href="{{ route('documents.bookings.gatepass', $booking) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-primary hover:text-primary-shade-1"
                                >Open full size</a>
                            </p>
                        @else
                            <p class="text-sm text-text-muted">Gatepass has not been uploaded by staff yet.</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($canViewEir || $canUploadEir)
                <div class="bg-surface-elevated">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-surface-inset"
                        @click="open = open === 'eir' ? null : 'eir'"
                        :aria-expanded="open === 'eir'"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-text">EIR</p>
                            <p class="text-xs text-text-muted">
                                {{ $hasEir ? 'Uploaded — tap to preview' : 'Not uploaded' }}
                            </p>
                        </div>
                        @if ($hasEir && $canViewEir)
                            <img
                                src="{{ route('documents.bookings.eir', $booking) }}"
                                alt=""
                                class="h-12 w-12 shrink-0 rounded-md border border-border object-cover"
                            >
                        @else
                            <x-ui.badge :tone="$hasEir ? 'success' : 'warning'">
                                {{ $hasEir ? 'Ready' : 'Missing' }}
                            </x-ui.badge>
                        @endif
                        <x-ui.icon
                            name="chevron-down"
                            size="size-4"
                            class="shrink-0 text-text-muted transition-transform"
                            x-bind:class="open === 'eir' && 'rotate-180'"
                        />
                    </button>
                    <div x-show="open === 'eir'" x-cloak class="space-y-4 border-t border-border px-4 py-4">
                        @if ($hasEir && $canViewEir)
                            <img
                                src="{{ route('documents.bookings.eir', $booking) }}"
                                alt="EIR for {{ $booking->booking_number }}"
                                class="max-h-96 w-full rounded-md border border-border object-contain"
                            >
                            <p class="text-sm">
                                <a
                                    href="{{ route('documents.bookings.eir', $booking) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-primary hover:text-primary-shade-1"
                                >Open full size</a>
                            </p>
                        @elseif (! $hasEir)
                            <p class="text-sm text-text-muted">EIR has not been uploaded yet.</p>
                        @endif

                        @can('uploadEir', $booking)
                            <div class="space-y-3 @if($hasEir) border-t border-border pt-4 @endif" x-data="{ open: false }">
                                <h3 class="text-sm font-semibold text-text">{{ $hasEir ? 'Replace EIR' : 'Upload EIR' }}</h3>
                                <form x-ref="eirForm" method="post" action="{{ route('driver.deliveries.eir.store', $booking) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <div>
                                        <x-ui.label for="eir">EIR image</x-ui.label>
                                        <input
                                            id="eir"
                                            type="file"
                                            name="eir"
                                            accept="image/jpeg,image/png,image/webp,image/gif"
                                            required
                                            class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                                        >
                                        <x-ui.field-error name="eir" />
                                    </div>
                                    @if ($hasEir)
                                        <x-ui.button type="button" @click="open = true">
                                            <x-ui.icon name="file-up" size="size-4" />
                                            Save EIR
                                        </x-ui.button>
                                    @else
                                        <x-ui.button type="submit">
                                            <x-ui.icon name="file-up" size="size-4" />
                                            Save EIR
                                        </x-ui.button>
                                    @endif
                                </form>
                                @if ($hasEir)
                                    <x-ui.confirm-dialog
                                        title="Replace this EIR?"
                                        description="The current EIR file will be overwritten."
                                        confirm-label="Replace EIR"
                                        cancel-label="Cancel"
                                        confirm-variant="danger"
                                        form-ref="eirForm"
                                    />
                                @endif
                            </div>
                        @endcan
                    </div>
                </div>
            @endif

            @if ($canViewPod || $canUploadPod)
                <div class="bg-surface-elevated">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-surface-inset"
                        @click="open = open === 'pod' ? null : 'pod'"
                        :aria-expanded="open === 'pod'"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-text">POD</p>
                            <p class="text-xs text-text-muted">
                                {{ $hasPod ? 'Uploaded — tap to preview' : 'Not uploaded' }}
                            </p>
                        </div>
                        @if ($hasPod && $canViewPod && ! empty($booking->pod->photo_paths))
                            <img
                                src="{{ route('documents.bookings.pod.photo', [$booking, 0]) }}"
                                alt=""
                                class="h-12 w-12 shrink-0 rounded-md border border-border object-cover"
                            >
                        @else
                            <x-ui.badge :tone="$hasPod ? 'success' : 'warning'">
                                {{ $hasPod ? 'Ready' : 'Missing' }}
                            </x-ui.badge>
                        @endif
                        <x-ui.icon
                            name="chevron-down"
                            size="size-4"
                            class="shrink-0 text-text-muted transition-transform"
                            x-bind:class="open === 'pod' && 'rotate-180'"
                        />
                    </button>
                    <div x-show="open === 'pod'" x-cloak class="space-y-4 border-t border-border px-4 py-4">
                        @if ($hasPod && $canViewPod)
                            <div>
                                <p class="mb-2 text-sm font-medium text-text">Photos</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($booking->pod->photo_paths as $index => $path)
                                        <a
                                            href="{{ route('documents.bookings.pod.photo', [$booking, $index]) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="block overflow-hidden rounded-md border border-border"
                                        >
                                            <img
                                                src="{{ route('documents.bookings.pod.photo', [$booking, $index]) }}"
                                                alt="POD photo {{ $index + 1 }} for {{ $booking->booking_number }}"
                                                class="max-h-64 w-full object-contain"
                                            >
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @elseif (! $hasPod)
                            <p class="text-sm text-text-muted">POD has not been uploaded yet.</p>
                        @endif

                        @can('uploadPod', $booking)
                            <div class="space-y-3 @if($hasPod) border-t border-border pt-4 @endif" x-data="{ open: false }">
                                <h3 class="text-sm font-semibold text-text">{{ $hasPod ? 'Replace POD' : 'Upload POD' }}</h3>
                                <form x-ref="podForm" method="post" action="{{ route('driver.deliveries.pod.store', $booking) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <div>
                                        <x-ui.label for="photos">POD photos (one or more)</x-ui.label>
                                        <input
                                            id="photos"
                                            type="file"
                                            name="photos[]"
                                            accept="image/jpeg,image/png,image/webp,image/gif"
                                            multiple
                                            required
                                            class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                                        >
                                        <x-ui.field-error name="photos" />
                                    </div>
                                    @if ($hasPod)
                                        <x-ui.button type="button" @click="open = true">
                                            <x-ui.icon name="file-up" size="size-4" />
                                            Save POD
                                        </x-ui.button>
                                    @else
                                        <x-ui.button type="submit">
                                            <x-ui.icon name="file-up" size="size-4" />
                                            Save POD
                                        </x-ui.button>
                                    @endif
                                </form>
                                @if ($hasPod)
                                    <x-ui.confirm-dialog
                                        title="Replace this POD?"
                                        description="The current POD photos will be overwritten."
                                        confirm-label="Replace POD"
                                        cancel-label="Cancel"
                                        confirm-variant="danger"
                                        form-ref="podForm"
                                    />
                                @endif
                            </div>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </x-ui.card>
@endif
