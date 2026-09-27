<h2>Route map</h2>
@if ($routeMap->hasImage())
    <p>
        @if ($routeMap->distanceKm !== null && $routeMap->durationMinutes !== null)
            Approx. {{ $routeMap->distanceKm }} km · {{ $routeMap->durationMinutes }} min driving
        @endif
    </p>
    <p>
        <img src="{{ $routeMap->imageUrl }}" alt="Route from pickup to dropoff for {{ $booking->booking_number }}" width="640" height="400" style="max-width:100%;height:auto;border:1px solid #e2e8f0;">
    </p>
@else
    <p><em>{{ $routeMap->message ?? 'Route map is not available.' }}</em></p>
@endif
