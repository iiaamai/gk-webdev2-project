@extends('layouts.customer')

@section('title', 'Home')

@section('content')
    <x-ui.page-header title="Welcome" :subtitle="'Signed in as '.$name" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card>
            <h2 class="text-base font-semibold text-text">My bookings</h2>
            <p class="mt-1 text-sm text-text-muted">Track gatepass, delivery status, and documents.</p>
            <div class="mt-4">
                <x-ui.button href="{{ route('customer.bookings.index') }}">
                    <x-ui.icon name="clipboard-list" size="size-4" />
                    View bookings
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-base font-semibold text-text">New booking</h2>
            <p class="mt-1 text-sm text-text-muted">Create a trip from the pricing list and available fleet.</p>
            <div class="mt-4">
                <x-ui.button href="{{ route('customer.bookings.create') }}" variant="secondary">
                    <x-ui.icon name="plus" size="size-4" />
                    Create booking
                </x-ui.button>
            </div>
        </x-ui.card>
    </div>
@endsection
