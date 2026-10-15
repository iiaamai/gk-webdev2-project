@extends('layouts.admin')

@section('title', 'Archived users')

@section('content')
    <x-ui.page-header title="Archived users" subtitle="Restore an account to the live list, or permanently delete it when related records are gone.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">
                Back to users
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($users->total() === 0)
        <x-ui.empty-state title="No archived users" icon="archive">
            <x-slot:description>Archived accounts will appear here.</x-slot:description>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Archived</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $user->email }}</td>
                    <td class="px-4 py-3">{{ $user->role->label() }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $user->archived_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3">
                        <x-ui.archive-row-actions
                            :restore-url="route('admin.users.restore', $user)"
                            :destroy-url="route('admin.users.force-destroy', $user)"
                            restore-title="Restore this user?"
                            restore-description="The account will return to the active list and can sign in again."
                            delete-title="Permanently delete this user?"
                            delete-description="This cannot be undone. Bookings, fleet assignment, and activity logs must already be gone."
                        />
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$users" />
    @endif
@endsection
