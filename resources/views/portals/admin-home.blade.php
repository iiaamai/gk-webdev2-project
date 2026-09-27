@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
    <h1>Admin overview</h1>
    <p>Signed in as <strong>{{ $name }}</strong>.</p>
    <p>Use the navigation for settings, pricing, fleet, users, bookings, earnings, and activity logs.</p>
@endsection
