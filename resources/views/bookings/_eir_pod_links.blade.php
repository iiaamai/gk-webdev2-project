@can('viewEir', $booking)
    @if ($booking->eir)
        <p>EIR: <a href="{{ route('documents.bookings.eir', $booking) }}">Download</a></p>
    @else
        <p>EIR: not uploaded yet.</p>
    @endif
@endcan

@can('viewPod', $booking)
    @if ($booking->pod)
        <p>POD photos:</p>
        <ul>
            @foreach ($booking->pod->photo_paths as $index => $path)
                <li><a href="{{ route('documents.bookings.pod.photo', [$booking, $index]) }}">Photo {{ $index + 1 }}</a></li>
            @endforeach
        </ul>
    @elseif (auth()->user()?->isCustomer())
        <p>POD: available when the delivery is completed.</p>
    @else
        <p>POD: not uploaded yet.</p>
    @endif
@endcan
