@if ($booking->rating)
    <h2>Trip rating</h2>
    <p>Score: <strong>{{ $booking->rating->score }}/5</strong></p>
    @if ($booking->rating->comment)
        <p>{{ $booking->rating->comment }}</p>
    @endif
@elseif (isset($canRate) && $canRate)
    <h2>Rate this trip</h2>
    <form method="post" action="{{ route('customer.bookings.rating.store', $booking) }}">
        @csrf
        <label for="score">Score (1–5)</label>
        <select id="score" name="score" required>
            @for ($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" @selected(old('score') == $i)>{{ $i }}</option>
            @endfor
        </select>
        <label for="comment">Comment (optional)</label>
        <textarea id="comment" name="comment" rows="3">{{ old('comment') }}</textarea>
        <button type="submit">Submit rating</button>
    </form>
@endif
