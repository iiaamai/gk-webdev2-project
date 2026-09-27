@extends('layouts.auth')

@section('title', 'Register driver')
@section('auth_mode', 'showcase-first')
@section('showcase_tagline', 'Join the GK fleet and accept matching deliveries')

@section('content')
    <h1 class="text-xl font-semibold text-text">Register as driver</h1>
    <p class="mt-1 text-sm text-text-muted">Create a driver account. Fleet vehicles are assigned by admin after registration.</p>

    <form method="post" action="{{ route('register.driver') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-ui.label for="name">Name</x-ui.label>
            <x-ui.input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            <x-ui.field-error name="name" />
        </div>

        <div>
            <x-ui.label for="email">Email</x-ui.label>
            <x-ui.input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            <x-ui.field-error name="email" />
        </div>

        <div>
            <x-ui.label for="mobile">Mobile</x-ui.label>
            <x-ui.input id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" />
            <x-ui.field-error name="mobile" />
        </div>

        <div>
            <x-ui.label for="password">Password</x-ui.label>
            <x-ui.input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-ui.field-error name="password" />
        </div>

        <div>
            <x-ui.label for="password_confirmation">Confirm password</x-ui.label>
            <x-ui.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-ui.button type="submit" class="w-full">
            <x-ui.icon name="user-plus" size="size-4" />
            Create account
        </x-ui.button>
    </form>
@endsection

@section('footer')
    <p>
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-shade-1">Log in</a>
    </p>
@endsection
