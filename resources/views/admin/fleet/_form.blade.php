@php
    $initialStatus = old('status', $vehicle?->status?->value ?? 'available');
    $initialDriverId = old('driver_id', $vehicle?->driver_id);
    $initialDriverId = $initialDriverId !== null && $initialDriverId !== '' ? (int) $initialDriverId : null;

    $driverOptions = collect($drivers ?? [])->map(function ($driver) use ($vehicle) {
        $assigned = $driver->assignedVehicle;

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'email' => $driver->email,
            'assignedPlate' => $assigned?->plate_number,
            'onThisVehicle' => $vehicle && $assigned && (int) $assigned->id === (int) $vehicle->id,
        ];
    })->values();

    $statusOptions = [
        'available' => ['label' => 'Available', 'tone' => 'success'],
        'in_use' => ['label' => 'In use', 'tone' => 'warning'],
        'maintenance' => ['label' => 'Maintenance', 'tone' => 'danger'],
    ];
@endphp

<form
    method="post"
    action="{{ $action }}"
    class="grid gap-4 lg:grid-cols-2 lg:items-start"
    x-data="{
        status: @js($initialStatus),
        driverId: @js($initialDriverId),
        search: '',
        drivers: @js($driverOptions),
        statusOptions: @js($statusOptions),
        get selectedDriver() {
            if (this.driverId === null || this.driverId === '') {
                return null;
            }
            return this.drivers.find((driver) => Number(driver.id) === Number(this.driverId)) ?? null;
        },
        get filteredDrivers() {
            const query = this.search.trim().toLowerCase();
            if (query === '') {
                return this.drivers;
            }
            return this.drivers.filter((driver) => {
                const haystack = `${driver.name} ${driver.email} ${driver.assignedPlate ?? ''}`.toLowerCase();
                return haystack.includes(query);
            });
        },
        selectDriver(id) {
            this.driverId = id === null ? null : Number(id);
        },
        isStatusActive(value) {
            return this.status === value;
        },
    }"
>
    @csrf
    @if ($vehicle)
        @method('PUT')
    @endif

    <x-ui.card class="order-1 lg:col-start-1 lg:row-start-1">
        <x-ui.section-heading icon="truck" title="Vehicle details" description="Plate, brand, color, and pricing type." />
        <div class="mt-4 space-y-4">
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
        </div>
    </x-ui.card>

    <x-ui.card class="order-2 lg:col-start-1 lg:row-start-2">
        <x-ui.section-heading icon="activity" title="Status" description="Click to set fleet status for this unit." />
        <input type="hidden" name="status" x-bind:value="status">
        <x-ui.field-error name="status" class="mt-2" />
        <div class="mt-4 flex flex-col gap-2">
            @foreach ($statusOptions as $value => $meta)
                <button
                    type="button"
                    class="flex items-center justify-between rounded-md border px-4 py-3 text-left text-sm transition-colors"
                    x-bind:class="isStatusActive('{{ $value }}')
                        ? 'border-primary bg-primary-subtle ring-2 ring-primary/30'
                        : 'border-border bg-surface-inset hover:border-border-strong hover:bg-surface-elevated'"
                    x-on:click="status = '{{ $value }}'"
                >
                    <span class="font-medium text-text">{{ $meta['label'] }}</span>
                    <x-ui.badge :tone="$meta['tone']">{{ $value }}</x-ui.badge>
                </button>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.card class="order-3 flex h-full flex-col lg:col-start-2 lg:row-start-1 lg:row-span-2 lg:self-stretch">
        <x-ui.section-heading icon="user" title="Assigned driver" description="Search and select a driver. Assigning someone from another vehicle clears that vehicle." />
        <input type="hidden" name="driver_id" x-bind:value="driverId ?? ''">
        <x-ui.field-error name="driver_id" class="mt-2" />

        <div class="mt-4 rounded-md border border-border bg-surface-inset px-3 py-2 text-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Current selection</p>
            <template x-if="selectedDriver">
                <div>
                    <p class="mt-1 font-medium text-text" x-text="selectedDriver.name"></p>
                    <p class="text-text-muted" x-text="selectedDriver.email"></p>
                </div>
            </template>
            <template x-if="!selectedDriver">
                <p class="mt-1 text-text-muted">Unassigned</p>
            </template>
        </div>

        <div class="mt-4">
            <x-ui.label for="driver_search">Search drivers</x-ui.label>
            <x-ui.input
                id="driver_search"
                type="search"
                placeholder="Name, email, or plate…"
                class="mt-1"
                x-model="search"
                autocomplete="off"
            />
        </div>

        <ul class="mt-3 max-h-64 flex-1 space-y-1 overflow-y-auto rounded-md border border-border p-1 lg:max-h-none lg:min-h-[12rem]">
            <li>
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors"
                    x-bind:class="driverId === null ? 'bg-primary-subtle text-primary-emphasis' : 'hover:bg-surface-inset'"
                    x-on:click="selectDriver(null)"
                >
                    <span class="font-medium">Unassigned</span>
                </button>
            </li>
            <template x-for="driver in filteredDrivers" :key="driver.id">
                <li>
                    <button
                        type="button"
                        class="flex w-full flex-col gap-0.5 rounded-md px-3 py-2 text-left text-sm transition-colors sm:flex-row sm:items-center sm:justify-between"
                        x-bind:class="Number(driverId) === Number(driver.id) ? 'bg-primary-subtle text-primary-emphasis' : 'hover:bg-surface-inset'"
                        x-on:click="selectDriver(driver.id)"
                    >
                        <span>
                            <span class="font-medium" x-text="driver.name"></span>
                            <span class="block text-xs text-text-muted" x-text="driver.email"></span>
                        </span>
                        <span
                            class="shrink-0 text-xs font-medium text-warning"
                            x-show="driver.assignedPlate && !driver.onThisVehicle"
                            x-text="`On ${driver.assignedPlate}`"
                        ></span>
                    </button>
                </li>
            </template>
            <li x-show="filteredDrivers.length === 0" class="px-3 py-4 text-center text-sm text-text-muted">
                No drivers match your search.
            </li>
        </ul>
    </x-ui.card>

    <div class="order-4 flex justify-start lg:col-start-1 lg:row-start-3">
        <x-ui.button type="submit">
            <x-ui.icon :name="$vehicle ? 'save' : 'plus'" size="size-4" />
            {{ $vehicle ? 'Save changes' : 'Create vehicle' }}
        </x-ui.button>
    </div>
</form>
