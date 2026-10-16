<ul class="mt-2 space-y-1 text-xs" aria-live="polite">
    <li class="flex items-center gap-1.5" :class="minLength ? 'text-success' : 'text-text-subtle'">
        <x-ui.icon name="check" size="size-3.5" />
        At least 8 characters
    </li>
    <li class="flex items-center gap-1.5" :class="hasLower ? 'text-success' : 'text-text-subtle'">
        <x-ui.icon name="check" size="size-3.5" />
        At least one lowercase letter
    </li>
    <li class="flex items-center gap-1.5" :class="hasUpper ? 'text-success' : 'text-text-subtle'">
        <x-ui.icon name="check" size="size-3.5" />
        At least one uppercase letter
    </li>
    <li class="flex items-center gap-1.5" :class="hasSpecial ? 'text-success' : 'text-text-subtle'">
        <x-ui.icon name="check" size="size-3.5" />
        At least one special character
    </li>
</ul>
