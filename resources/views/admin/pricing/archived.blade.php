@extends('layouts.admin')

@section('title', 'Archived pricing')

@section('content')
    <x-ui.page-header title="Archived pricing" subtitle="Restore a pricing row to the live list, or permanently delete it when related records are gone.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.pricing.index') }}" variant="secondary">
                Back to pricing
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($pricings->total() === 0)
        <x-ui.empty-state title="No archived pricing" icon="archive">
            <x-slot:description>Archived pricing rows will appear here.</x-slot:description>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Vehicle type</th>
                    <th class="px-4 py-3 font-medium">Amount (PHP)</th>
                    <th class="px-4 py-3 font-medium">Archived</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($pricings as $pricing)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $pricing->vehicle_type }}</td>
                    <td class="px-4 py-3">₱{{ number_format((float) $pricing->amount, 2) }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $pricing->archived_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3">
                        <x-ui.archive-row-actions
                            :restore-url="route('admin.pricing.restore', $pricing)"
                            :destroy-url="route('admin.pricing.force-destroy', $pricing)"
                            restore-title="Restore this pricing row?"
                            restore-description="The pricing row will return to the active list."
                            delete-title="Permanently delete this pricing row?"
                            delete-description="This cannot be undone. Bookings and fleet vehicles that still use this row must already be gone."
                        />
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$pricings" />
    @endif
@endsection
