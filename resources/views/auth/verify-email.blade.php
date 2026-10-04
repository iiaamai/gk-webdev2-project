@extends('layouts.auth')

@section('title', 'Verify email')
@section('subtitle', 'Confirm your email address')

@section('content')
    <h1 class="text-xl font-semibold text-text">Verify your email</h1>
    <p class="mt-1 text-sm text-text-muted">
        Thanks for signing up. Please verify your email address to continue.
        @if (! config('gk.mail_enabled'))
            While mail is a placeholder, seeded and new accounts may already be marked verified.
        @endif
    </p>

    <form method="post" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-ui.button type="submit" class="w-full">
            <x-ui.icon name="mail" size="size-4" />
            Resend verification email
        </x-ui.button>
    </form>

    <div class="mt-3">
        <x-ui.logout-button class="w-full justify-center" />
    </div>
@endsection
