@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')
    <h1>Activity logs</h1>
    <p>Latest 100 events (IP captured; IP geo lookup not enabled).</p>

    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->timezone('Asia/Manila')->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user?->email ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No activity logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
