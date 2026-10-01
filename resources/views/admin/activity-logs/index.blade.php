@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')
    <x-ui.page-header
        title="Activity logs"
        subtitle="Search and filter audit events (IP captured; geo lookup not enabled)."
    />

    <x-ui.list-filters
        :action="route('admin.activity-logs.index')"
        :clear-url="route('admin.activity-logs.index')"
        :q="$search"
        search-placeholder="Action, description, or user"
        :filters-active="$filtersActive"
        :log-action="$logActionFilter"
        :log-action-options="$logActionOptions"
    />

    @if ($logs->total() === 0)
        @if ($filtersActive)
            <x-ui.list-no-results :clear-url="route('admin.activity-logs.index')" />
        @else
            <x-ui.empty-state title="No activity logged yet" icon="activity">
                <x-slot:description>Actions across the app will appear here as they occur.</x-slot:description>
            </x-ui.empty-state>
        @endif
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">When</th>
                    <th class="px-4 py-3 font-medium">User</th>
                    <th class="px-4 py-3 font-medium">Action</th>
                    <th class="px-4 py-3 font-medium">Description</th>
                    <th class="px-4 py-3 font-medium">IP</th>
                </tr>
            </x-slot:head>
            @foreach ($logs as $log)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-text-muted">{{ $log->created_at?->timezone('Asia/Manila')->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $log->user?->email ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge tone="neutral">{{ $log->action }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-text-muted">{{ $log->description }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$logs" />
    @endif
@endsection
