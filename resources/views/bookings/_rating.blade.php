@if ($booking->rating)
    <x-ui.section-heading icon="star" title="Trip rating" />
    <div class="mt-4 space-y-2 text-sm">
        <p class="flex items-center gap-1 font-medium text-text" aria-label="{{ $booking->rating->score }} out of 5">
            @for ($i = 1; $i <= 5; $i++)
                <x-ui.icon
                    name="star"
                    size="size-5"
                    @class([
                        'fill-warning text-warning' => $i <= $booking->rating->score,
                        'text-border' => $i > $booking->rating->score,
                    ])
                />
            @endfor
            <span class="ms-2">{{ $booking->rating->score }}/5</span>
        </p>
        @if ($booking->rating->comment)
            <p class="text-text-muted">{{ $booking->rating->comment }}</p>
        @endif
    </div>
@elseif (isset($canRate) && $canRate)
    <x-ui.section-heading icon="star" title="Rate this trip" description="Share feedback after a completed delivery." />
    <form
        method="post"
        action="{{ route('customer.bookings.rating.store', $booking) }}"
        class="mt-4 space-y-4"
        x-data="{ score: {{ (int) old('score', 5) }} }"
    >
        @csrf
        <div>
            <x-ui.label for="score">Score (1–5)</x-ui.label>
            <input type="hidden" name="score" id="score" :value="score" required>
            <div class="mt-2 flex items-center gap-1" role="group" aria-label="Star rating">
                @for ($i = 1; $i <= 5; $i++)
                    <button
                        type="button"
                        class="rounded p-0.5 text-border focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        @click="score = {{ $i }}"
                        :class="score >= {{ $i }} ? 'text-warning' : 'text-border'"
                        :aria-pressed="score >= {{ $i }}"
                        aria-label="{{ $i }} star{{ $i === 1 ? '' : 's' }}"
                    >
                        <svg class="size-7 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2l2.9 6.9L22 10l-5 4.6L18.2 22 12 18.2 5.8 22 7 14.6 2 10l7.1-1.1L12 2z" />
                        </svg>
                    </button>
                @endfor
                <span class="ms-2 text-sm text-text-muted" x-text="score + '/5'"></span>
            </div>
            <x-ui.field-error name="score" />
        </div>
        <div>
            <x-ui.label for="comment">Comment (optional)</x-ui.label>
            <x-ui.textarea id="comment" name="comment" rows="3">{{ old('comment') }}</x-ui.textarea>
            <x-ui.field-error name="comment" />
        </div>
        <x-ui.button type="submit">Submit rating</x-ui.button>
    </form>
@endif
