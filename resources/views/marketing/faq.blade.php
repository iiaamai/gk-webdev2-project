@extends('layouts.marketing')

@section('title', 'FAQ')

@section('content')
    <section class="bg-neutral-50" x-data="{ faq: null }">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <h1 class="text-2xl font-semibold text-brand-navy">FAQ</h1>
            <div class="mt-8 space-y-3">
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 1 ? null : 1">
                        Do I need an account to book?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 1" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Yes. Sign up, then create a booking from your account.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 2 ? null : 2">
                        How do I book a trip?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 2" x-cloak class="mt-2 text-sm text-brand-charcoal/80">After you sign in, choose pickup, drop-off, cargo, and a vehicle type. The fare is taken from the live pricing list for that type.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 3 ? null : 3">
                        How do I track my booking?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 3" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Open the booking in your account. Status moves from request through in transit to completed, under one booking number.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 4 ? null : 4">
                        Can I cancel a booking?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 4" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Contact us if you need to cancel. Cancellation is handled by the office, usually before the trip starts.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 5 ? null : 5">
                        What documents will I see?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 5" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Your account shows trip documents such as equipment interchange (EIR) and proof of delivery when they are available, plus your invoice for settlement.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 6 ? null : 6">
                        How do I pay?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 6" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Pay later with an invoice. Scan the company QR Ph; we mark the invoice paid once payment is confirmed.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 7 ? null : 7">
                        How is the fare calculated?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 7" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Each vehicle type has a published amount and capacity in PHP. You pick a type when you book; that listed fare is what you settle.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 8 ? null : 8">
                        How do I update my account details?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 8" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Sign in and open your profile to change name, contact details, and password.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
