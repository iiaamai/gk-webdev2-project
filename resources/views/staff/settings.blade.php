@extends('layouts.staff')

@section('title', 'Settings')

@section('content')
    <x-ui.page-header title="Settings" subtitle="Account and session." />

    <div class="max-w-xl space-y-4">
        <x-ui.card>
            <x-ui.section-heading icon="user" title="Account" />
            <dl class="mt-4 grid grid-cols-[6rem_1fr] gap-x-3 gap-y-2 text-sm">
                <dt class="text-text-muted">Name</dt>
                <dd class="font-medium">{{ auth()->user()->name }}</dd>
                <dt class="text-text-muted">Email</dt>
                <dd>{{ auth()->user()->email }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="log-out" title="Sign out" description="End your staff session on this device." />
            <form method="post" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <x-ui.button type="submit" variant="secondary">
                    <x-ui.icon name="log-out" size="size-4" />
                    Log out
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
