@can('uploadEir', $booking)
    <h2>{{ $booking->eir ? 'Replace EIR' : 'Upload EIR' }}</h2>
    <form method="post" action="{{ $eirAction }}" enctype="multipart/form-data">
        @csrf
        <label for="eir">EIR image</label>
        <input id="eir" type="file" name="eir" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <button type="submit">Save EIR</button>
    </form>
@endcan

@can('uploadPod', $booking)
    <h2>{{ $booking->pod ? 'Replace POD' : 'Upload POD' }}</h2>
    <form method="post" action="{{ $podAction }}" enctype="multipart/form-data">
        @csrf
        <label for="photos">POD photos (one or more)</label>
        <input id="photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
        <label for="signature">Digital signature image</label>
        <input id="signature" type="file" name="signature" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <button type="submit">Save POD</button>
    </form>
@endcan
