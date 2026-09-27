@extends('layouts.admin')

@section('title', 'Create user')

@section('content')
    <x-ui.page-header title="Create user" subtitle="Add a customer, driver, staff, or admin account.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to users
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.users._form', ['user' => null, 'action' => route('admin.users.store')])
@endsection
