@extends('layouts.staff')

@section('title', 'Settings')

@section('content')
    <x-ui.page-header title="Settings" subtitle="Account and session." />

    <div class="max-w-xl space-y-4">
        <x-ui.card>
            <x-ui.section-heading icon="user" title="Profile" description="Update your name, mobile, and photo." />
            <div class="mt-4">
                @include('profile._form', ['user' => auth()->user()])
            </div>
            <dl class="mt-6 grid grid-cols-[6rem_1fr] gap-x-3 gap-y-2 border-t border-border pt-4 text-sm">
                <dt class="text-text-muted">Email</dt>
                <dd>{{ auth()->user()->email }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="log-out" title="Sign out" description="End your staff session on this device." />
            <div class="mt-4">
                <x-ui.logout-button />
            </div>
        </x-ui.card>
    </div>
@endsection
