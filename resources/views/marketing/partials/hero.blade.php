<section class="relative overflow-hidden text-brand-white">
    <img
        src="{{ asset('images/gk-fleet.jpg') }}"
        alt=""
        class="absolute inset-0 h-full w-full object-cover"
    >
    <div class="absolute inset-0 bg-black/60" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-crimson">Urban &amp; regional truck logistics</p>
        <h1 class="mt-4 max-w-xl text-4xl font-semibold tracking-tight sm:text-5xl">Connecting the world through logistics</h1>
        <p class="mt-4 max-w-xl text-base text-white/80 sm:text-lg">
            Book a truck, follow your trip from request to delivery, and settle with an invoice — one place to book, track, and pay.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('register.customer') }}" class="inline-flex items-center rounded-md bg-brand-crimson px-5 py-2.5 text-sm font-medium text-brand-white hover:bg-brand-crimson/90">Sign up</a>
            <a href="{{ route('login') }}" class="inline-flex items-center rounded-md border border-white/30 px-5 py-2.5 text-sm font-medium text-brand-white hover:bg-white/10">Sign in</a>
        </div>
    </div>
</section>
