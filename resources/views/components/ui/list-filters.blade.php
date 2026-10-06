@props([
    'action',
    'clearUrl',
    'q' => '',
    'searchPlaceholder' => 'Search…',
    'filtersActive' => false,
    'status' => null,
    'statusOptions' => [],
    'statusLabel' => 'Status',
    'role' => null,
    'roleOptions' => [],
    'logAction' => null,
    'logActionOptions' => [],
    'dateFrom' => null,
    'dateTo' => null,
    'showDateRange' => false,
])

<form method="get" action="{{ $action }}" class="mb-4 flex flex-col gap-3 rounded-md border border-border bg-surface-elevated p-4 lg:flex-row lg:flex-wrap lg:items-end">
    <div class="min-w-[12rem] flex-1">
        <x-ui.label for="list_filter_q">Search</x-ui.label>
        <x-ui.input
            id="list_filter_q"
            name="q"
            type="search"
            value="{{ $q }}"
            placeholder="{{ $searchPlaceholder }}"
            class="mt-1"
        />
    </div>

    @if (count($statusOptions) > 0)
        <div class="min-w-[10rem]">
            <x-ui.label for="list_filter_status">{{ $statusLabel }}</x-ui.label>
            <x-ui.select id="list_filter_status" name="status" class="mt-1">
                <option value="">All</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) $status === (string) $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
    @endif

    @if (count($roleOptions) > 0)
        <div class="min-w-[10rem]">
            <x-ui.label for="list_filter_role">Role</x-ui.label>
            <x-ui.select id="list_filter_role" name="role" class="mt-1">
                <option value="">All roles</option>
                @foreach ($roleOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) $role === (string) $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
    @endif

    @if (count($logActionOptions) > 0)
        <div class="min-w-[10rem]">
            <x-ui.label for="list_filter_log_action">Action</x-ui.label>
            <x-ui.select id="list_filter_log_action" name="log_action" class="mt-1">
                <option value="">All actions</option>
                @foreach ($logActionOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) $logAction === (string) $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
    @endif

    @if ($showDateRange)
        <div class="min-w-[10rem]">
            <x-ui.label for="list_filter_date_from">From</x-ui.label>
            <x-ui.input id="list_filter_date_from" type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1" />
        </div>
        <div class="min-w-[10rem]">
            <x-ui.label for="list_filter_date_to">To</x-ui.label>
            <x-ui.input id="list_filter_date_to" type="date" name="date_to" value="{{ $dateTo }}" class="mt-1" />
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        <x-ui.button type="submit">Apply</x-ui.button>
        @if ($filtersActive)
            <x-ui.button href="{{ $clearUrl }}" variant="secondary">Clear</x-ui.button>
        @endif
    </div>
</form>
