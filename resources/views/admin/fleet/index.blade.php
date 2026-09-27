@extends('layouts.admin')

@section('title', 'Fleet')

@section('content')
    <x-ui.page-header title="Fleet" subtitle="Vehicles available for bookings.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.fleet.create') }}">
                <x-ui.icon name="plus" size="size-4" />
                Add vehicle
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($vehicles->isEmpty())
        <x-ui.empty-state title="No vehicles yet" icon="truck">
            <x-slot:description>Add fleet units so bookings can assign a matching vehicle type.</x-slot:description>
            <x-slot:actions>
                <x-ui.button href="{{ route('admin.fleet.create') }}">
                    <x-ui.icon name="plus" size="size-4" />
                    Add vehicle
                </x-ui.button>
            </x-slot:actions>
        </x-ui.empty-state>
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
                    $statusTone = match ($vehicle->status->value) {
                        'available' => 'success',
                        'in_use' => 'warning',
                        'maintenance' => 'danger',
                        default => 'neutral',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $vehicle->plate_number }}</td>
                    <td class="px-4 py-3">{{ $vehicle->brand }}</td>
                    <td class="px-4 py-3">{{ $vehicle->color ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $vehicle->pricing?->vehicle_type ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $vehicle->pricing?->capacity_kg ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge :tone="$statusTone">{{ $vehicle->status->value }}</x-ui.badge>
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
                            <x-ui.button href="{{ route('admin.fleet.edit', $vehicle) }}" variant="secondary" class="!py-1.5 !text-xs">
                                <x-ui.icon name="pencil" size="size-3.5" />
                                Edit
                            </x-ui.button>
                            <form method="post" action="{{ route('admin.fleet.destroy', $vehicle) }}" onsubmit="return confirm('Archive this vehicle?');">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" class="!py-1.5 !text-xs !text-danger">
                                    <x-ui.icon name="archive" size="size-3.5" />
                                    Archive
                                </x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
@endsection
