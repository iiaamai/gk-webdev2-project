@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <x-ui.page-header
        title="Settings"
        subtitle="Company profile, booking sequence, and map defaults."
    />

    <x-ui.card class="mb-4 max-w-xl">
        <x-ui.section-heading icon="user" title="My profile" description="Your name, mobile, and photo." />
        <div class="mt-4">
            @include('profile._form', ['user' => auth()->user()])
        </div>
        <dl class="mt-6 grid grid-cols-[6rem_1fr] gap-x-3 gap-y-2 border-t border-border pt-4 text-sm">
            <dt class="text-text-muted">Email</dt>
            <dd>{{ auth()->user()->email }}</dd>
        </dl>
    </x-ui.card>

    <x-ui.card class="max-w-xl">
        <form method="post" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-ui.section-heading icon="settings" title="Company settings" description="Company profile, booking sequence, and map defaults." />

            <div>
                <x-ui.label for="company_name">Company name</x-ui.label>
                <x-ui.input id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required />
                <x-ui.field-error name="company_name" />
            </div>

            <div>
                <x-ui.label for="support_email">Support email</x-ui.label>
                <x-ui.input id="support_email" type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" required />
                <x-ui.field-error name="support_email" />
            </div>

            <div>
                <x-ui.label for="booking_seq">Booking sequence (next number)</x-ui.label>
                <x-ui.input id="booking_seq" type="number" name="booking_seq" value="{{ old('booking_seq', $settings['booking_seq']) }}" min="1" required />
                <x-ui.field-error name="booking_seq" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-ui.label for="map_center_lat">Map center latitude</x-ui.label>
                    <x-ui.input id="map_center_lat" type="number" step="any" name="map_center_lat" value="{{ old('map_center_lat', $settings['map_center_lat']) }}" required />
                    <x-ui.field-error name="map_center_lat" />
                </div>
                <div>
                    <x-ui.label for="map_center_lng">Map center longitude</x-ui.label>
                    <x-ui.input id="map_center_lng" type="number" step="any" name="map_center_lng" value="{{ old('map_center_lng', $settings['map_center_lng']) }}" required />
                    <x-ui.field-error name="map_center_lng" />
                </div>
            </div>

            <div>
                <x-ui.label for="map_zoom">Map zoom (optional)</x-ui.label>
                <x-ui.input id="map_zoom" type="number" name="map_zoom" value="{{ old('map_zoom', $settings['map_zoom']) }}" min="1" max="22" />
                <x-ui.field-error name="map_zoom" />
            </div>

            <x-ui.button type="submit">
                <x-ui.icon name="save" size="size-4" />
                Save settings
            </x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-4 max-w-xl">
        <x-ui.section-heading icon="log-out" title="Sign out" description="End your admin session on this device." />
        <div class="mt-4">
            <x-ui.logout-button />
        </div>
    </x-ui.card>
@endsection
