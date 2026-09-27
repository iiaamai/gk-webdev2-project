@php
    $redirectTo = $redirectTo ?? null;
@endphp

@can('uploadEir', $booking)
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-text">{{ $booking->eir ? 'Replace EIR' : 'Upload EIR' }}</h3>
        <form method="post" action="{{ $eirAction }}" enctype="multipart/form-data" class="space-y-3">
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
            <x-ui.button type="submit">
                <x-ui.icon name="file-up" size="size-4" />
                Save EIR
            </x-ui.button>
        </form>
    </div>
@endcan

@can('uploadPod', $booking)
    <div @class(['space-y-3', 'mt-6' => auth()->user()?->can('uploadEir', $booking)])>
        <h3 class="text-sm font-semibold text-text">{{ $booking->pod ? 'Replace POD' : 'Upload POD' }}</h3>
        <form method="post" action="{{ $podAction }}" enctype="multipart/form-data" class="space-y-3">
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
            <div>
                <x-ui.label for="signature">Digital signature image</x-ui.label>
                <input
                    id="signature"
                    type="file"
                    name="signature"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    required
                    class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                >
                <x-ui.field-error name="signature" />
            </div>
            <x-ui.button type="submit">
                <x-ui.icon name="file-up" size="size-4" />
                Save POD
            </x-ui.button>
        </form>
    </div>
@endcan
