@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    @php
        use App\Enums\UserRole;
        $roleOptions = collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->value])->all();
    @endphp

    <x-ui.page-header title="User management" subtitle="Customers, drivers, staff, and system admins.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.create') }}">
                <x-ui.icon name="user-plus" size="size-4" />
                Create user
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.list-filters
        :action="route('admin.users.index')"
        :clear-url="route('admin.users.index')"
        :q="$search"
        search-placeholder="Name or email"
        :filters-active="$filtersActive"
        :role="$roleFilter"
        :role-options="$roleOptions"
    />

    @if ($users->total() === 0)
        @if ($filtersActive)
            <x-ui.list-no-results :clear-url="route('admin.users.index')" />
        @else
            <x-ui.empty-state title="No users" icon="users">
                <x-slot:description>Create staff or other roles from here.</x-slot:description>
                <x-slot:actions>
                    <x-ui.button href="{{ route('admin.users.create') }}">
                        <x-ui.icon name="user-plus" size="size-4" />
                        Create user
                    </x-ui.button>
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($users as $user)
                @php
                    $roleTone = match ($user->role->value) {
                        'system_admin' => 'primary',
                        'staff' => 'info',
                        'driver' => 'warning',
                        default => 'neutral',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-text-muted">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge :tone="$roleTone">{{ $user->role->value }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-ui.button href="{{ route('admin.users.show', $user) }}" variant="ghost" class="!py-1.5 !text-xs">
                                <x-ui.icon name="eye" size="size-3.5" />
                                View
                            </x-ui.button>
                            <x-ui.button href="{{ route('admin.users.edit', $user) }}" variant="secondary" class="!py-1.5 !text-xs">
                                <x-ui.icon name="pencil" size="size-3.5" />
                                Edit
                            </x-ui.button>
                            @can('delete', $user)
                                <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Archive this user?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" class="!py-1.5 !text-xs !text-danger">
                                        <x-ui.icon name="archive" size="size-3.5" />
                                        Archive
                                    </x-ui.button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.pagination :paginator="$users" />
    @endif
@endsection
