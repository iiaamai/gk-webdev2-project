@include('bookings._documents_panel', [
    'booking' => $booking,
    'showUploads' => true,
    'eirAction' => route('driver.deliveries.eir.store', $booking),
    'podAction' => route('driver.deliveries.pod.store', $booking),
    'description' => 'Gatepass, EIR, and POD. Expand a row to preview or upload.',
])
