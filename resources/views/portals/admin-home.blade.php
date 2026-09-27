@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
    <x-ui.page-header title="Admin overview" subtitle="Master data, bookings, earnings, and activity." />

    <x-ui.card>
        <p class="text-sm text-text-muted">Signed in as <span class="font-medium text-text">{{ $name }}</span>.</p>
        <p class="mt-2 text-sm text-text-muted">Use the sidebar for settings, pricing, fleet, users, bookings, earnings, and activity logs.</p>
    </x-ui.card>
@endsection
