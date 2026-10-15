@extends('layouts.admin')

@section('title', 'Archived bookings')

@section('content')
    <x-ui.page-header title="Archived bookings" subtitle="Restore a trip to the live list, or permanently delete it when related records are gone.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.bookings.index') }}" variant="secondary">
                Back to bookings
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($bookings->total() === 0)
        <x-ui.empty-state title="No archived bookings" icon="archive">
            <x-slot:description>Archived trips will appear here.</x-slot:description>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Number</th>
                    <th class="px-4 py-3 font-medium">Customer</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Archived</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($bookings as $booking)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $booking->booking_number }}</td>
                    <td class="px-4 py-3">{{ $booking->customer?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $booking->status->label() }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $booking->archived_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3">
                        <x-ui.archive-row-actions
                            :restore-url="route('admin.bookings.restore', $booking)"
                            :destroy-url="route('admin.bookings.force-destroy', $booking)"
                            restore-title="Restore this booking?"
                            restore-description="The booking will return to the active list."
                            delete-title="Permanently delete this booking?"
                            delete-description="This cannot be undone. Invoice, EIR, POD, and rating records must already be gone."
                        />
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$bookings" />
    @endif
@endsection
