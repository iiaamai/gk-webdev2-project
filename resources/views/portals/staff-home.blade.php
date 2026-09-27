@extends('layouts.staff')

@section('title', 'Overview')

@section('content')
    <x-ui.page-header title="Staff overview" subtitle="Update bookings and upload the first gatepass." />

    <x-ui.card>
        <p class="text-sm text-text-muted">Welcome, <span class="font-medium text-text">{{ $name }}</span>.</p>
        <p class="mt-2 text-sm text-text-muted">Manage bookings — update details, upload gatepass, and cancel before gatepass is issued.</p>
        <div class="mt-4">
            <x-ui.button href="{{ route('staff.bookings.index') }}" variant="primary">
                <x-ui.icon name="clipboard-list" size="size-4" />
                Open bookings
            </x-ui.button>
        </div>
    </x-ui.card>
@endsection
