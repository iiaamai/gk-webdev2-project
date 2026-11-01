@props([
    'user',
    'timeInUrl',
    'timeOutUrl',
    'status',
    'compact' => false,
])

@php
    use App\Enums\DriverAttendanceStatus;
    $canTimeIn = auth()->user()?->can('timeIn', $user) && $status !== DriverAttendanceStatus::TimedIn;
    $canTimeOut = auth()->user()?->can('timeOut', $user) && $status === DriverAttendanceStatus::TimedIn;
@endphp

<div @class(['flex flex-wrap items-center gap-2', 'justify-end' => $compact])>
    <x-ui.badge :tone="$status->badgeTone()">{{ $status->label() }}</x-ui.badge>

    @if ($canTimeIn)
        <form method="post" action="{{ $timeInUrl }}" class="inline">
            @csrf
            <x-ui.button type="submit" variant="secondary" @class(['!py-1.5 !text-xs' => $compact])>
                Time in
            </x-ui.button>
        </form>
    @endif

    @if ($canTimeOut)
        <form method="post" action="{{ $timeOutUrl }}" class="inline">
            @csrf
            <x-ui.button type="submit" variant="ghost" @class(['!py-1.5 !text-xs' => $compact])>
                Time out
            </x-ui.button>
        </form>
    @endif
</div>
