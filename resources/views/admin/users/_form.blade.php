@php
    $initialRole = old('role', $user?->role?->value ?? 'customer');
    $displayName = old('name', $user?->name ?? '');
@endphp

<form
    method="post"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-4"
    x-data="{ role: @js($initialRole), displayName: @js($displayName) }"
>
    @csrf
    @if ($user)
        @method('PUT')
    @endif

    <div class="grid gap-4 lg:grid-cols-2 lg:items-start">
        <div class="space-y-4">
            <x-ui.card>
                <x-ui.section-heading icon="user" title="Basic information" description="Name, email, and contact number." />
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="flex flex-col items-center gap-2 sm:items-start">
                        @if ($user?->hasAvatar())
                            <x-ui.user-avatar :user="$user" size="lg" />
                        @else
                            <div
                                class="inline-flex size-16 shrink-0 items-center justify-center rounded-full text-lg font-semibold"
                                :class="{
                                    'bg-primary text-text-on-primary': role === 'system_admin',
                                    'bg-info/20 text-info': role === 'staff',
                                    'bg-warning/25 text-warning': role === 'driver',
                                    'bg-primary-tone-2 text-primary-shade-1': role === 'customer',
                                }"
                                x-text="(() => {
                                    const parts = displayName.trim().split(/\s+/).filter(Boolean);
                                    let initials = parts.slice(0, 2).map(p => p.charAt(0).toUpperCase()).join('');
                                    return initials || '?';
                                })()"
                                aria-hidden="true"
                            ></div>
                        @endif
                        <div class="w-full max-w-[12rem]">
                            <x-ui.label for="avatar">Photo</x-ui.label>
                            <input
                                id="avatar"
                                type="file"
                                name="avatar"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="block w-full text-xs text-text-muted file:me-2 file:rounded-md file:border-0 file:bg-primary file:px-2 file:py-1.5 file:text-xs file:font-medium file:text-text-on-primary"
                            >
                            <x-ui.field-error name="avatar" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-ui.label for="name">Name</x-ui.label>
                                <x-ui.input id="name" name="name" value="{{ old('name', $user?->name) }}" required @input="displayName = $event.target.value" />
                                <x-ui.field-error name="name" />
                            </div>

                            <div>
                                <x-ui.label for="email">Email</x-ui.label>
                                <x-ui.input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required />
                                <x-ui.field-error name="email" />
                            </div>

                            <div>
                                <x-ui.label for="mobile">Mobile</x-ui.label>
                                <x-ui.input id="mobile" name="mobile" value="{{ old('mobile', $user?->mobile) }}" />
                                <x-ui.field-error name="mobile" />
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card x-show="role === 'driver'" x-cloak>
                <x-ui.section-heading icon="truck" title="Fleet vehicle" description="Optional. Assign an existing fleet unit; type, plate, and capacity come from that vehicle’s pricing." />
                <div class="mt-4">
                    <x-ui.label for="vehicle_id">Assigned vehicle</x-ui.label>
                    <x-ui.select id="vehicle_id" name="vehicle_id">
                        <option value="">Unassigned</option>
                        @foreach ($vehicles ?? [] as $fleetVehicle)
                            <option
                                value="{{ $fleetVehicle->id }}"
                                @selected((string) old('vehicle_id', $user?->assignedVehicle?->id) === (string) $fleetVehicle->id)
                            >
                                {{ $fleetVehicle->plate_number }} — {{ $fleetVehicle->pricing?->vehicle_type ?? $fleetVehicle->type }} ({{ $fleetVehicle->brand }}{{ $fleetVehicle->color ? ', '.$fleetVehicle->color : '' }})
                            </option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.field-error name="vehicle_id" />
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card>
                <x-ui.section-heading icon="shield" title="Role" description="Determines portal access and capabilities." />
                <div class="mt-4">
                    <x-ui.label for="role">Role</x-ui.label>
                    <x-ui.select id="role" name="role" required x-model="role">
                        @foreach (['customer', 'driver', 'staff', 'system_admin'] as $roleOption)
                            <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.field-error name="role" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-heading
                    icon="lock"
                    title="Password"
                    :description="$user ? 'Leave blank to keep the current password.' : 'Set the initial login password.'"
                />
                <div class="mt-4 space-y-4">
                    <div>
                        <x-ui.label for="password">Password</x-ui.label>
                        @if ($user)
                            <x-ui.input id="password" type="password" name="password" autocomplete="new-password" />
                        @else
                            <x-ui.input id="password" type="password" name="password" autocomplete="new-password" required />
                        @endif
                        <x-ui.field-error name="password" />
                    </div>

                    <div>
                        <x-ui.label for="password_confirmation">Confirm password</x-ui.label>
                        @if ($user)
                            <x-ui.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
                        @else
                            <x-ui.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required />
                        @endif
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-ui.button type="submit">
            <x-ui.icon :name="$user ? 'save' : 'user-plus'" size="size-4" />
            {{ $user ? 'Update user' : 'Create user' }}
        </x-ui.button>
        @if ($user)
            <x-ui.button href="{{ route('admin.users.show', $user) }}" variant="secondary">
                Cancel
            </x-ui.button>
        @else
            <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">
                Cancel
            </x-ui.button>
        @endif
    </div>
</form>
