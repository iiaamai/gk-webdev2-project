@php
    $redirectTo = $redirectTo ?? null;
    $hasEir = (bool) $booking->eir;
    $hasPod = (bool) $booking->pod;
    $only = $only ?? null;
    $showEir = $only === null || $only === 'eir';
    $showPod = $only === null || $only === 'pod';
@endphp

@if ($showEir)
    @can('uploadEir', $booking)
        <div class="space-y-3" x-data="{ open: false }">
            <h3 class="text-sm font-semibold text-text">{{ $hasEir ? 'Replace EIR' : 'Upload EIR' }}</h3>
            <form x-ref="eirForm" method="post" action="{{ $eirAction }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @if ($redirectTo)
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                @endif
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
@endif

@if ($showPod)
    @can('uploadPod', $booking)
        <div @class([
            'space-y-3',
            'mt-6' => $only === null && auth()->user()?->can('uploadEir', $booking),
        ]) x-data="{ open: false }">
            <h3 class="text-sm font-semibold text-text">{{ $hasPod ? 'Replace POD' : 'Upload POD' }}</h3>
            <form x-ref="podForm" method="post" action="{{ $podAction }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @if ($redirectTo)
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                @endif
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
@endif
