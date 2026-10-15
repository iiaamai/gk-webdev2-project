@extends('layouts.auth')

@section('title', 'Confirm your email')
@section('subtitle', 'Check your inbox to finish signup')

@section('content')
    <h1 class="text-xl font-semibold text-text">Check your email</h1>
    <p class="mt-1 text-sm text-text-muted">
        {{ session('status') ?: 'We sent a confirmation link to your email. Your account is created only after you open that link.' }}
    </p>
    <p class="mt-4 text-sm text-text-muted">
        The link expires in 24 hours. If you do not see the message, check spam.
    </p>
@endsection

@section('footer')
    <p>
        Already confirmed?
        <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-shade-1">Log in</a>
    </p>
@endsection
