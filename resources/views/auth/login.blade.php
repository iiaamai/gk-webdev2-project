@extends('layouts.auth')

@section('title', 'Login')
@section('auth_mode', 'form-first')
@section('showcase_tagline', 'Urban & regional truck logistics')

@section('content')
    <h1 class="text-xl font-semibold text-text">Login</h1>
    <p class="mt-1 text-sm text-text-muted">Use your email and password to continue.</p>

    <form method="post" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-ui.label for="email">Email</x-ui.label>
            <x-ui.input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-ui.field-error name="email" />
        </div>

        <div>
            <x-ui.label for="password">Password</x-ui.label>
            <x-ui.input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-ui.field-error name="password" />
        </div>

        <label class="flex items-center gap-2 text-sm text-text-muted">
            <input type="checkbox" name="remember" class="size-4 rounded border-border text-primary focus:ring-primary">
            Remember me
        </label>

        <x-ui.button type="submit" class="w-full">
            <x-ui.icon name="log-in" size="size-4" />
            Log in
        </x-ui.button>
    </form>
@endsection

@section('footer')
    <p>
        New here?
        <a href="{{ route('register.customer') }}" class="font-medium text-primary hover:text-primary-shade-1">Register as customer</a>
        or
        <a href="{{ route('register.driver') }}" class="font-medium text-primary hover:text-primary-shade-1">driver</a>
    </p>
@endsection
