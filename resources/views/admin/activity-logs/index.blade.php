@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')
    <x-ui.page-header
        title="Activity logs"
        subtitle="Search and filter audit events. IP is always stored; location and browser geo when available."
    />

    <x-ui.list-filters
        :action="route('admin.activity-logs.index')"
        :clear-url="route('admin.activity-logs.index')"
        :q="$search"
        search-placeholder="Action, description, or user"
        :filters-active="$filtersActive"
        :log-action="$logActionFilter"
        :log-action-options="$logActionOptions"
        show-date-range
        :date-from="$dateFrom"
        :date-to="$dateTo"
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
        <div class="space-y-3" x-data="{ open: null }">
            @foreach ($logs as $log)
                @php
                    $subjectKey = $log->subject_type.'#'.$log->subject_id;
                    $subjectUrl = $subjectUrls[$subjectKey] ?? null;
                    $location = $log->ip_location
                        ?? (($log->geo_lat !== null && $log->geo_lng !== null)
                            ? number_format((float) $log->geo_lat, 4).', '.number_format((float) $log->geo_lng, 4)
                            : null);
                @endphp
                <div class="rounded-lg border border-border bg-surface-elevated">
                    <button
                        type="button"
                        class="flex w-full flex-col gap-2 px-4 py-3 text-left hover:bg-surface-inset sm:flex-row sm:items-center sm:justify-between"
                        @click="open = open === {{ $log->id }} ? null : {{ $log->id }}"
                        :aria-expanded="open === {{ $log->id }}"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge tone="neutral">{{ $log->action }}</x-ui.badge>
                                <span class="text-xs text-text-subtle">
                                    {{ $log->created_at?->timezone('Asia/Manila')->format('Y-m-d H:i') }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-text-muted">{{ $log->description }}</p>
                        </div>
                        <div class="shrink-0 text-xs text-text-subtle sm:text-right">
                            <p>{{ $log->user?->email ?? '—' }}</p>
                            <p class="mt-0.5">{{ $log->ip_address ?? '—' }}@if ($location) · {{ $location }}@endif</p>
                        </div>
                    </button>
                    <div x-show="open === {{ $log->id }}" x-cloak class="border-t border-border px-4 py-3 text-sm">
                        <dl class="grid gap-2 sm:grid-cols-[8rem_1fr]">
                            <dt class="text-text-muted">IP</dt>
                            <dd>{{ $log->ip_address ?? '—' }}</dd>
                            <dt class="text-text-muted">IP location</dt>
                            <dd>{{ $log->ip_location ?? '—' }}</dd>
                            <dt class="text-text-muted">Browser geo</dt>
                            <dd>
                                @if ($log->geo_lat !== null && $log->geo_lng !== null)
                                    {{ number_format((float) $log->geo_lat, 7) }}, {{ number_format((float) $log->geo_lng, 7) }}
                                @else
                                    —
                                @endif
                            </dd>
                            @if ($subjectUrl)
                                <dt class="text-text-muted">Subject</dt>
                                <dd>
                                    <a href="{{ $subjectUrl }}" class="font-medium text-primary hover:text-primary-shade-1">Open related record</a>
                                </dd>
                            @endif
                        </dl>
                        @if (! empty($log->properties))
                            <p class="mt-3 font-medium text-text">Properties</p>
                            <pre class="mt-1 max-h-48 overflow-auto rounded-md bg-surface-inset p-3 text-xs text-text-muted">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <x-ui.pagination :paginator="$logs" />
    @endif
@endsection
