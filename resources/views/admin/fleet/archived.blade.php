@extends('layouts.admin')

@section('title', 'Archived fleet')

@section('content')
    <x-ui.page-header title="Archived fleet" subtitle="Restore a vehicle to the live list, or permanently delete it when related records are gone.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.fleet.index') }}" variant="secondary">
                Back to fleet
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($vehicles->total() === 0)
        <x-ui.empty-state title="No archived vehicles" icon="archive">
            <x-slot:description>Archived fleet units will appear here.</x-slot:description>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Plate</th>
                    <th class="px-4 py-3 font-medium">Brand</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Archived</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($vehicles as $vehicle)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $vehicle->plate_number }}</td>
                    <td class="px-4 py-3">{{ $vehicle->brand }}</td>
                    <td class="px-4 py-3">{{ $vehicle->type }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $vehicle->archived_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3">
                        <x-ui.archive-row-actions
                            :restore-url="route('admin.fleet.restore', $vehicle)"
                            :destroy-url="route('admin.fleet.force-destroy', $vehicle)"
                            restore-title="Restore this vehicle?"
                            restore-description="The vehicle will return to the active fleet list."
                            delete-title="Permanently delete this vehicle?"
                            delete-description="This cannot be undone. Bookings that still reference this vehicle must already be gone."
                        />
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$vehicles" />
    @endif
@endsection
