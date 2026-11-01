@extends('layouts.staff')

@section('title', 'Drivers')

@section('content')
    <x-ui.page-header
        title="Drivers"
        subtitle="Record time-in and time-out before drivers can log in."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('staff.drivers.map') }}" variant="secondary">
                <x-ui.icon name="map" size="size-4" />
                Driver map
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.list-filters
        :action="route('staff.drivers.index')"
        :clear-url="route('staff.drivers.index')"
        :q="$search"
        search-placeholder="Name or email"
        :filters-active="$filtersActive"
    />

    @if ($drivers->total() === 0)
        @if ($filtersActive)
            <x-ui.list-no-results :clear-url="route('staff.drivers.index')" />
        @else
            <x-ui.empty-state title="No drivers" icon="users">
                <x-slot:description>Driver accounts appear here when created by an admin.</x-slot:description>
            </x-ui.empty-state>
        @endif
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Vehicle</th>
                    <th class="px-4 py-3 font-medium">Attendance</th>
                </tr>
            </x-slot:head>
            @foreach ($drivers as $driver)
                @php
                    $status = $statuses[$driver->id];
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $driver->name }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $driver->email }}</td>
                    <td class="px-4 py-3 text-sm text-text-muted">
                        @if ($driver->assignedVehicle)
                            {{ $driver->assignedVehicle->plate_number }}
                            · {{ $driver->assignedVehicle->pricing?->vehicle_type ?? '—' }}
                        @else
                            Unassigned
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @include('drivers._attendance_controls', [
                            'user' => $driver,
                            'status' => $status,
                            'timeInUrl' => route('staff.drivers.time-in', $driver),
                            'timeOutUrl' => route('staff.drivers.time-out', $driver),
                            'compact' => true,
                        ])
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$drivers" />
    @endif
@endsection
