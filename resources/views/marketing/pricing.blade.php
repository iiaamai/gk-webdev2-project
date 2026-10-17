@extends('layouts.marketing')

@section('title', 'Pricing')

@section('content')
    <section class="bg-brand-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h1 class="text-2xl font-semibold text-brand-navy">Transparent pricing</h1>
            <p class="mt-2 max-w-2xl text-sm text-brand-charcoal/80">Fares come from the live pricing list — vehicle type, capacity, and amount in PHP. No surprise quotes at the gate.</p>

            @if ($pricings->isEmpty())
                <p class="mt-8 rounded-xl border border-brand-navy/10 p-5 text-sm text-brand-charcoal/80">Fares will appear here when the pricing list is published. Sign up to book once types are listed.</p>
            @else
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($pricings as $pricing)
                        @php
                            $image = match ($pricing->vehicle_type) {
                                'L300 van' => 'images/gk-truck-l300.png',
                                '4-wheeler truck' => 'images/gk-truck-4-wheeler.png',
                                '6-wheeler truck' => 'images/gk-truck-6-wheeler.png',
                                'Reefer / specialized' => 'images/gk-truck-reefer.png',
                                default => null,
                            };
                        @endphp
                        <article class="flex flex-col overflow-hidden rounded-xl border border-brand-navy/10">
                            @if ($image)
                                <div class="flex h-40 items-center justify-center bg-neutral-50 px-4 py-3">
                                    <img
                                        src="{{ asset($image) }}"
                                        alt="{{ $pricing->vehicle_type }}"
                                        class="max-h-full w-full object-contain"
                                    >
                                </div>
                            @endif
                            <div class="p-5">
                                <p class="text-sm text-brand-charcoal/70">{{ $pricing->vehicle_type }}</p>
                                <p class="mt-1 text-lg font-semibold text-brand-navy">₱{{ number_format((float) $pricing->amount, 2) }}</p>
                                <p class="mt-1 text-sm text-brand-charcoal/70">{{ number_format((int) $pricing->capacity_kg) }} kg capacity</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
