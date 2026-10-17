@extends('layouts.auth')

@section('title', 'Forgot password')
@section('auth_mode', 'form-first')

@section('content')
    <h1 class="text-xl font-semibold text-text">Forgot password</h1>

    @if (filled($resetEmail))
        <p class="mt-1 text-sm text-text-muted">Set a new password for <span class="font-medium text-text">{{ $resetEmail }}</span>.</p>

        <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf

            <x-ui.password-field />

            <div>
                <x-ui.label for="password_confirmation">Confirm password</x-ui.label>
                <x-ui.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <x-ui.button type="submit" class="w-full">
                <x-ui.icon name="save" size="size-4" />
                Update password
            </x-ui.button>
        </form>
    @else
        <p class="mt-1 text-sm text-text-muted">Enter the email on your customer or driver account. If we find it, you can set a new password here.</p>

        <form method="post" action="{{ route('password.lookup') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <x-ui.label for="email">Email</x-ui.label>
                <x-ui.input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <x-ui.field-error name="email" />
            </div>

            <x-ui.button type="submit" class="w-full">
                Continue
            </x-ui.button>
        </form>
    @endif
@endsection

@section('footer')
    <p>
        Remembered it?
        <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-shade-1">Log in</a>
    </p>
@endsection
