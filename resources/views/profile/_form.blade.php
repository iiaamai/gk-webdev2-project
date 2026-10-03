@php
    $user = $user ?? auth()->user();
    $action = $action ?? route('profile.update');
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @method('PUT')

    <div class="flex items-start gap-4">
        <x-ui.user-avatar :user="$user" size="lg" />
        <div class="min-w-0 flex-1 space-y-4">
            <div>
                <x-ui.label for="profile_name">Name</x-ui.label>
                <x-ui.input id="profile_name" name="name" value="{{ old('name', $user->name) }}" required />
                <x-ui.field-error name="name" />
            </div>

            <div>
                <x-ui.label for="profile_mobile">Mobile</x-ui.label>
                <x-ui.input id="profile_mobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" placeholder="09XXXXXXXXX" />
                <x-ui.field-error name="mobile" />
            </div>

            <div>
                <x-ui.label for="profile_avatar">Profile photo</x-ui.label>
                <input
                    id="profile_avatar"
                    type="file"
                    name="avatar"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    class="block w-full text-sm text-text-muted file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-on-primary"
                >
                <p class="mt-1 text-xs text-text-muted">Optional. JPEG, PNG, WebP, or GIF up to 5 MB.</p>
                <x-ui.field-error name="avatar" />
            </div>
        </div>
    </div>

    <x-ui.button type="submit">
        Save profile
    </x-ui.button>
</form>
