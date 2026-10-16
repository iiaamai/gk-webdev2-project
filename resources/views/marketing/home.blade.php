<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GK Logistics — Connecting the world through logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-white text-brand-charcoal antialiased" x-data="{ menuOpen: false, faq: null }">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-navy">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-brand-white">
                <x-brand.mark size="sm" />
                <span class="text-sm font-semibold tracking-tight sm:text-base">GK Logistics</span>
            </a>

            <nav class="hidden items-center gap-6 text-sm text-white/80 md:flex">
                <a href="#how" class="hover:text-brand-white">How it works</a>
                <a href="#roles" class="hover:text-brand-white">Roles</a>
                <a href="#pricing" class="hover:text-brand-white">Pricing</a>
                <a href="#faq" class="hover:text-brand-white">FAQ</a>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-brand-white hover:bg-white/10">Sign in</a>
                <a href="{{ route('register.customer') }}" class="inline-flex items-center rounded-md bg-brand-crimson px-4 py-2 text-sm font-medium text-brand-white hover:bg-brand-crimson/90">Sign up</a>
            </div>

            <button type="button" class="inline-flex rounded-md p-2 text-brand-white md:hidden" @click="menuOpen = ! menuOpen" aria-label="Open menu">
                <x-ui.icon name="menu" size="size-6" />
            </button>
        </div>

        <div x-show="menuOpen" x-cloak class="border-t border-white/10 px-4 py-3 md:hidden">
            <div class="flex flex-col gap-3 text-sm text-white/90">
                <a href="#how" @click="menuOpen = false">How it works</a>
                <a href="#roles" @click="menuOpen = false">Roles</a>
                <a href="#pricing" @click="menuOpen = false">Pricing</a>
                <a href="#faq" @click="menuOpen = false">FAQ</a>
                <a href="{{ route('login') }}" class="font-medium">Sign in</a>
                <a href="{{ route('register.customer') }}" class="font-medium text-brand-crimson">Sign up</a>
            </div>
        </div>
    </header>

    <section class="bg-brand-navy text-brand-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-crimson">Urban &amp; regional truck logistics</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">Connecting the world through logistics</h1>
                <p class="mt-4 max-w-xl text-base text-white/80 sm:text-lg">
                    Book a truck, get a gatepass, match a driver, and finish the trip with EIR, proof of delivery, and invoice — one portal for customers, drivers, staff, and admin.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register.customer') }}" class="inline-flex items-center rounded-md bg-brand-crimson px-5 py-2.5 text-sm font-medium text-brand-white hover:bg-brand-crimson/90">Sign up as customer</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-md border border-white/30 px-5 py-2.5 text-sm font-medium text-brand-white hover:bg-white/10">Sign in</a>
                </div>
                <p class="mt-4 text-sm text-white/70">
                    Driving with us?
                    <a href="{{ route('register.driver') }}" class="font-medium text-brand-white underline decoration-brand-crimson underline-offset-4">Register as a driver</a>
                </p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <p class="text-sm font-medium text-white/70">Trip lifecycle</p>
                <ol class="mt-4 space-y-3 text-sm">
                    <li class="rounded-lg bg-brand-white/10 px-4 py-3">1. Customer books a vehicle type from the pricing list</li>
                    <li class="rounded-lg bg-brand-white/10 px-4 py-3">2. Staff uploads gatepass — driver accepts a matching job</li>
                    <li class="rounded-lg bg-brand-white/10 px-4 py-3">3. In transit with route view, then EIR + POD</li>
                    <li class="rounded-lg bg-brand-white/10 px-4 py-3">4. Invoice unpaid/paid, QR Ph for pay later</li>
                </ol>
            </div>
        </div>
    </section>

    <section id="how" class="bg-brand-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-semibold text-brand-navy">How it works</h2>
            <p class="mt-2 max-w-2xl text-sm text-brand-charcoal/80">Four roles. One booking number. Status from pending to completed.</p>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm font-semibold text-brand-crimson">01</p>
                    <h3 class="mt-2 font-semibold text-brand-navy">Request</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Customers pick pickup, drop-off, cargo, and a priced vehicle type.</p>
                </article>
                <article class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm font-semibold text-brand-crimson">02</p>
                    <h3 class="mt-2 font-semibold text-brand-navy">Dispatch</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Office issues the first gatepass. Drivers only see jobs that match their truck.</p>
                </article>
                <article class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm font-semibold text-brand-crimson">03</p>
                    <h3 class="mt-2 font-semibold text-brand-navy">Deliver</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Accept, go in transit, upload EIR and proof of delivery photos.</p>
                </article>
                <article class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm font-semibold text-brand-crimson">04</p>
                    <h3 class="mt-2 font-semibold text-brand-navy">Settle</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Pay later invoice, optional QR scan, staff/admin mark paid.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="roles" class="bg-neutral-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-semibold text-brand-navy">Built for every role</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-2">
                <article class="rounded-xl bg-brand-white p-6 shadow-sm">
                    <h3 class="font-semibold text-brand-navy">Customers</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Self-register, book trips, track status, and settle invoices.</p>
                </article>
                <article class="rounded-xl bg-brand-white p-6 shadow-sm">
                    <h3 class="font-semibold text-brand-navy">Drivers</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Accept matching jobs, update delivery status, submit EIR and POD.</p>
                </article>
                <article class="rounded-xl bg-brand-white p-6 shadow-sm">
                    <h3 class="font-semibold text-brand-navy">Staff</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">First gatepass, booking updates, and invoice payment checks — without fleet or user admin.</p>
                </article>
                <article class="rounded-xl bg-brand-white p-6 shadow-sm">
                    <h3 class="font-semibold text-brand-navy">System admin</h3>
                    <p class="mt-2 text-sm text-brand-charcoal/80">Full control: fleet, users, pricing, archives, and overrides.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="pricing" class="bg-brand-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-semibold text-brand-navy">Transparent pricing</h2>
            <p class="mt-2 max-w-2xl text-sm text-brand-charcoal/80">Fares come from the live pricing list — vehicle type, capacity, and amount in PHP. No surprise quotes at the gate.</p>
            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm text-brand-charcoal/70">Van / light</p>
                    <p class="mt-1 text-lg font-semibold text-brand-navy">Listed in-app</p>
                </div>
                <div class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm text-brand-charcoal/70">4-wheeler truck</p>
                    <p class="mt-1 text-lg font-semibold text-brand-navy">Listed in-app</p>
                </div>
                <div class="rounded-xl border border-brand-navy/10 p-5">
                    <p class="text-sm text-brand-charcoal/70">Pay later</p>
                    <p class="mt-1 text-lg font-semibold text-brand-navy">Invoice + QR Ph</p>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="bg-neutral-50">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-semibold text-brand-navy">FAQ</h2>
            <div class="mt-8 space-y-3">
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 1 ? null : 1">
                        Do I need an account to book?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 1" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Yes. Sign up as a customer, then create a booking from your portal.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 2 ? null : 2">
                        Can drivers register themselves?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 2" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Yes. Driver signup is available; an admin assigns the fleet vehicle after registration.</p>
                </div>
                <div class="rounded-xl bg-brand-white p-4">
                    <button type="button" class="flex w-full items-center justify-between text-left font-medium text-brand-navy" @click="faq = faq === 3 ? null : 3">
                        How do I pay?
                        <x-ui.icon name="chevron-down" size="size-4" />
                    </button>
                    <p x-show="faq === 3" x-cloak class="mt-2 text-sm text-brand-charcoal/80">Pay later invoices in this demo. Scan the company QR Ph and wait for staff or admin to mark paid.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-brand-navy text-white/80">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <p class="font-semibold text-brand-white">GK Logistics</p>
                <p class="mt-1 text-sm">Connecting the world through logistics</p>
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <a href="{{ route('login') }}" class="hover:text-brand-white">Sign in</a>
                <a href="{{ route('register.customer') }}" class="hover:text-brand-white">Sign up</a>
                <a href="{{ route('register.driver') }}" class="hover:text-brand-white">Driver signup</a>
            </div>
        </div>
    </footer>

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
