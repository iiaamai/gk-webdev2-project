@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    @php
        $roleTone = match ($user->role->value) {
            'system_admin' => 'primary',
            'staff' => 'info',
            'driver' => 'warning',
            default => 'neutral',
        };
    @endphp

    <x-ui.page-header title="{{ $user->name }}" subtitle="{{ $user->email }}">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to users
            </x-ui.button>
            <x-ui.button href="{{ route('admin.users.edit', $user) }}">
                <x-ui.icon name="pencil" size="size-4" />
                Edit
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-2 lg:items-start">
        <x-ui.card>
            <x-ui.section-heading icon="user" title="Basic information" />
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                <x-ui.user-avatar :name="$user->name" :role="$user->role" />
                <dl class="min-w-0 flex-1 grid grid-cols-1 gap-x-3 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-text-muted">Name</dt>
                        <dd class="font-medium">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Email</dt>
                        <dd>{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Mobile</dt>
                        <dd>{{ $user->mobile ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </x-ui.card>

        <div class="space-y-4">
            <x-ui.card>
                <x-ui.section-heading icon="shield" title="Role" />
                <dl class="mt-4 grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2 text-sm">
                    <dt class="text-text-muted">Role</dt>
                    <dd><x-ui.badge :tone="$roleTone">{{ $user->role->value }}</x-ui.badge></dd>
                    <dt class="text-text-muted">Email verified</dt>
                    <dd>{{ $user->email_verified_at ? $user->email_verified_at->timezone(config('app.timezone'))->format('M j, Y g:i A') : 'Not verified' }}</dd>
                </dl>
            </x-ui.card>

            @if ($user->isDriver())
                <x-ui.card>
                    <x-ui.section-heading icon="truck" title="Fleet vehicle" />
                    <dl class="mt-4 grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2 text-sm">
                        @if ($user->assignedVehicle)
                            <dt class="text-text-muted">Plate</dt>
                            <dd>{{ $user->assignedVehicle->plate_number }}</dd>
                            <dt class="text-text-muted">Brand</dt>
                            <dd>{{ $user->assignedVehicle->brand }}</dd>
                            <dt class="text-text-muted">Color</dt>
                            <dd>{{ $user->assignedVehicle->color ?? '—' }}</dd>
                            <dt class="text-text-muted">Type</dt>
                            <dd>{{ $user->assignedVehicle->pricing?->vehicle_type ?? '—' }}</dd>
                            <dt class="text-text-muted">Capacity (kg)</dt>
                            <dd>{{ $user->assignedVehicle->pricing?->capacity_kg ?? '—' }}</dd>
                        @else
                            <dt class="text-text-muted">Assignment</dt>
                            <dd>Unassigned</dd>
                        @endif
                    </dl>
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection
