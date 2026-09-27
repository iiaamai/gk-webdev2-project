<form method="post" action="{{ $action }}" class="space-y-4" @if ($multipart ?? false) enctype="multipart/form-data" @endif>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    @include('bookings._trip_fields', get_defined_vars())

    <x-ui.button type="submit">
        <x-ui.icon name="save" size="size-4" />
        {{ $submitLabel ?? 'Save booking' }}
    </x-ui.button>
</form>
