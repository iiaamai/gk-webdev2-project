@extends('layouts.admin')

@section('title', 'Earnings')

@section('content')
    <h1>Earnings</h1>
    <p>Computed from completed bookings (payout snapshot). Charts can polish this in F10.</p>

    <dl>
        <dt>Completed trips</dt><dd>{{ $totalCompleted }}</dd>
        <dt>Total payout</dt><dd>₱{{ number_format((float) $totalPayout, 2) }}</dd>
        <dt>Average payout</dt><dd>₱{{ number_format((float) $averagePayout, 2) }}</dd>
    </dl>

    <h2>By month</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Bookings</th>
                <th>Payout</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($monthly as $row)
                <tr>
                    <td>{{ $row->month }}</td>
                    <td>{{ $row->bookings }}</td>
                    <td>₱{{ number_format((float) $row->payout, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No completed bookings yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
