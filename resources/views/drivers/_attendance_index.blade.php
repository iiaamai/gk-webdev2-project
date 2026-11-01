@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\User> $drivers */
    /** @var \Illuminate\Support\Collection<int, \App\Enums\DriverAttendanceStatus> $statuses */
@endphp

<x-ui.page-header
    title="Drivers"
    subtitle="Record time-in and time-out before drivers can log in."
>
    <x-slot:actions>
        <x-ui.button href="{{ $mapRoute }}" variant="secondary">
            <x-ui.icon name="map" size="size-4" />
            Driver map
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.list-filters
    :action="$indexRoute"
    :clear-url="$clearRoute"
    :q="$search"
    search-placeholder="Name or email"
    :filters-active="$filtersActive"
/>

@if ($drivers->total() === 0)
    @if ($filtersActive)
        <x-ui.list-no-results :clear-url="$clearRoute" />
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
                <td class="px-4 py-3 font-medium">
                    @isset($showRoute)
                        <a href="{{ route($showRoute, $driver) }}" class="font-medium text-primary hover:underline">
                            {{ $driver->name }}
                        </a>
                    @else
                        {{ $driver->name }}
                    @endisset
                </td>
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
                        'timeInUrl' => route($timeInRoute, $driver),
                        'timeOutUrl' => route($timeOutRoute, $driver),
                        'compact' => true,
                    ])
                </td>
            </tr>
        @endforeach
    </x-ui.table>

    <x-ui.pagination :paginator="$drivers" />
@endif
