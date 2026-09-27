<form method="post" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($vehicle)
        @method('PUT')
    @endif

    <div>
        <x-ui.label for="plate_number">Plate number</x-ui.label>
        <x-ui.input id="plate_number" name="plate_number" value="{{ old('plate_number', $vehicle?->plate_number) }}" required />
        <x-ui.field-error name="plate_number" />
    </div>

    <div>
        <x-ui.label for="brand">Brand</x-ui.label>
        <x-ui.input id="brand" name="brand" value="{{ old('brand', $vehicle?->brand) }}" required />
        <x-ui.field-error name="brand" />
    </div>

    <div>
        <x-ui.label for="color">Color</x-ui.label>
        <x-ui.input id="color" name="color" value="{{ old('color', $vehicle?->color) }}" required />
        <x-ui.field-error name="color" />
    </div>

    <div>
        <x-ui.label for="pricing_id">Vehicle type (pricing)</x-ui.label>
        <x-ui.select id="pricing_id" name="pricing_id" required>
            <option value="">Select pricing…</option>
            @foreach ($pricings ?? [] as $pricing)
                <option value="{{ $pricing->id }}" @selected((string) old('pricing_id', $vehicle?->pricing_id) === (string) $pricing->id)>
                    {{ $pricing->vehicle_type }} — ₱{{ number_format((float) $pricing->amount, 2) }} ({{ $pricing->capacity_kg }} kg)
                </option>
            @endforeach
        </x-ui.select>
        <x-ui.field-error name="pricing_id" />
    </div>

    <div>
        <x-ui.label for="status">Status</x-ui.label>
        <x-ui.select id="status" name="status" required>
            @foreach (['available', 'in_use', 'maintenance'] as $status)
                <option value="{{ $status }}" @selected(old('status', $vehicle?->status?->value ?? 'available') === $status)>{{ $status }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.field-error name="status" />
    </div>

    <div>
        <x-ui.label for="driver_id">Assigned driver</x-ui.label>
        <x-ui.select id="driver_id" name="driver_id">
            <option value="">Unassigned</option>
            @foreach ($drivers ?? [] as $driver)
                <option value="{{ $driver->id }}" @selected((string) old('driver_id', $vehicle?->driver_id) === (string) $driver->id)>
                    {{ $driver->name }} — {{ $driver->email }}
                </option>
            @endforeach
        </x-ui.select>
        <x-ui.field-error name="driver_id" />
    </div>

    <x-ui.button type="submit">
        <x-ui.icon :name="$vehicle ? 'save' : 'plus'" size="size-4" />
        {{ $vehicle ? 'Update' : 'Create' }}
    </x-ui.button>
</form>
