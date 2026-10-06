@extends('layouts.staff')

@section('title', 'Fleet')

@section('content')
    @php
        use App\Enums\VehicleStatus;
        $statusOptions = collect(VehicleStatus::cases())->mapWithKeys(fn (VehicleStatus $status) => [$status->value => $status->label()])->all();
    @endphp

    <x-ui.page-header title="Fleet" subtitle="View and update vehicles. Contact an administrator to add or archive units." />

    <x-ui.list-filters
        :action="route('staff.fleet.index')"
        :clear-url="route('staff.fleet.index')"
        :q="$search"
        search-placeholder="Plate, brand, or driver name"
        :filters-active="$filtersActive"
        :status="$statusFilter"
        :status-options="$statusOptions"
    />

    @if ($vehicles->total() === 0)
        @if ($filtersActive)
            <x-ui.list-no-results :clear-url="route('staff.fleet.index')" />
        @else
            <x-ui.empty-state title="No vehicles yet" icon="truck">
                <x-slot:description>Fleet units will appear here once an administrator adds them.</x-slot:description>
            </x-ui.empty-state>
        @endif
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Plate</th>
                    <th class="px-4 py-3 font-medium">Brand</th>
                    <th class="px-4 py-3 font-medium">Color</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Capacity (kg)</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Assigned driver</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($vehicles as $vehicle)
                @php
                    $statusTone = $vehicle->status->badgeTone();
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $vehicle->plate_number }}</td>
                    <td class="px-4 py-3">{{ $vehicle->brand }}</td>
                    <td class="px-4 py-3">{{ $vehicle->color ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $vehicle->pricing?->vehicle_type ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $vehicle->pricing?->capacity_kg ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge :tone="$statusTone">{{ $vehicle->status->label() }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        @if ($vehicle->driver)
                            <div class="font-medium">{{ $vehicle->driver->name }}</div>
                            <div class="text-xs text-text-muted">{{ $vehicle->driver->email }}</div>
                        @else
                            <span class="text-text-muted">Unassigned</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-ui.button href="{{ route('staff.fleet.edit', $vehicle) }}" variant="secondary" class="!py-1.5 !text-xs">
                                <x-ui.icon name="pencil" size="size-3.5" />
                                Edit
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$vehicles" />
    @endif
@endsection
