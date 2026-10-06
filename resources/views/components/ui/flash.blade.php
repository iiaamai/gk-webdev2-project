@php
    $hasStatus = filled(session('status'));
    $hasErrors = $errors->any();
@endphp

@if ($hasStatus || $hasErrors)
    <div
        class="pointer-events-none fixed inset-x-0 top-20 z-[70] flex justify-center px-4 print:hidden sm:top-4 sm:justify-end sm:pe-4 lg:pe-6"
        aria-live="{{ $hasErrors ? 'assertive' : 'polite' }}"
    >
        <div class="pointer-events-auto flex w-full max-w-md flex-col gap-2">
            @if ($hasStatus)
                <div
                    class="rounded-lg border border-success/25 bg-success-subtle px-4 py-3 text-sm text-success shadow-lg"
                    role="status"
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition.opacity.duration.200ms
                    x-init="setTimeout(() => show = false, 5000)"
                    x-cloak
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 flex-1">{{ session('status') }}</p>
                        <button
                            type="button"
                            class="shrink-0 rounded p-0.5 text-success/80 hover:bg-success/10 hover:text-success focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                            @click="show = false"
                            aria-label="Dismiss message"
                        >
                            <x-ui.icon name="x" size="size-4" />
                        </button>
                    </div>
                </div>
            @endif

            @if ($hasErrors)
                <div
                    class="rounded-lg border border-danger/25 bg-danger-subtle px-4 py-3 text-sm text-danger shadow-lg"
                    role="alert"
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition.opacity.duration.200ms
                    x-init="setTimeout(() => show = false, 8000)"
                    x-cloak
                >
                    <div class="flex items-start justify-between gap-3">
                        <ul class="min-w-0 flex-1 list-disc space-y-1 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button
                            type="button"
                            class="shrink-0 rounded p-0.5 text-danger/80 hover:bg-danger/10 hover:text-danger focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                            @click="show = false"
                            aria-label="Dismiss errors"
                        >
                            <x-ui.icon name="x" size="size-4" />
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
