@if ($booking->rating)
    <x-ui.section-heading icon="star" title="Trip rating" />
    <div class="mt-4 space-y-2 text-sm">
        <p class="font-medium text-text">{{ $booking->rating->score }}/5</p>
        @if ($booking->rating->comment)
            <p class="text-text-muted">{{ $booking->rating->comment }}</p>
        @endif
    </div>
@elseif (isset($canRate) && $canRate)
    <x-ui.section-heading icon="star" title="Rate this trip" description="Share feedback after a completed delivery." />
    <form method="post" action="{{ route('customer.bookings.rating.store', $booking) }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <x-ui.label for="score">Score (1–5)</x-ui.label>
            <x-ui.select id="score" name="score" required>
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected((string) old('score') === (string) $i)>{{ $i }}</option>
                @endfor
            </x-ui.select>
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
