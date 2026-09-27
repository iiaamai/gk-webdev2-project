@extends('layouts.admin')

@section('title', 'Edit pricing')

@section('content')
    <x-ui.page-header title="Edit pricing" subtitle="Update vehicle type or amount.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.pricing.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="max-w-xl">
        <form method="post" action="{{ route('admin.pricing.update', $pricing) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-ui.label for="vehicle_type">Vehicle type</x-ui.label>
                <x-ui.input id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type', $pricing->vehicle_type) }}" required />
                <x-ui.field-error name="vehicle_type" />
            </div>

            <div>
                <x-ui.label for="amount">Amount (PHP)</x-ui.label>
                <x-ui.input id="amount" type="number" step="0.01" name="amount" value="{{ old('amount', $pricing->amount) }}" min="0" required />
                <x-ui.field-error name="amount" />
            </div>

            <div>
                <x-ui.label for="capacity_kg">Capacity (kg)</x-ui.label>
                <x-ui.input id="capacity_kg" type="number" name="capacity_kg" value="{{ old('capacity_kg', $pricing->capacity_kg) }}" min="1" required />
                <x-ui.field-error name="capacity_kg" />
            </div>

            <x-ui.button type="submit">
                <x-ui.icon name="save" size="size-4" />
                Update
            </x-ui.button>
        </form>
    </x-ui.card>
@endsection
