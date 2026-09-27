@extends('layouts.admin')

@section('title', 'Edit user')

@section('content')
    <x-ui.page-header title="Edit user" subtitle="{{ $user->name }}">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.show', $user) }}" variant="secondary">
                <x-ui.icon name="eye" size="size-4" />
                View profile
            </x-ui.button>
            <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to users
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.users._form', ['user' => $user, 'action' => route('admin.users.update', $user)])
@endsection
