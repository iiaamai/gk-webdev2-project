@props([
    'variant' => 'secondary',
    'label' => 'Log out',
    'iconSize' => 'size-4',
])

<div class="inline-flex" x-data="{ open: false }">
    <form x-ref="logoutForm" method="post" action="{{ route('logout') }}">
        @csrf
        <x-ui.button
            type="button"
            variant="{{ $variant }}"
            {{ $attributes }}
            @click="open = true"
        >
            <x-ui.icon name="log-out" size="{{ $iconSize }}" />
            @if ($label !== '')
                {{ $label }}
            @else
                <span class="sr-only">Log out</span>
            @endif
        </x-ui.button>
    </form>

    <x-ui.confirm-dialog
        title="Log out?"
        description="You will need to sign in again to continue."
        confirm-label="Log out"
        cancel-label="Stay signed in"
        confirm-variant="danger"
        form-ref="logoutForm"
    />
</div>
