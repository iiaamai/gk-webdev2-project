@props([
    'id' => 'password',
    'name' => 'password',
    'label' => 'Password',
    'autocomplete' => 'new-password',
])

<div
    x-data="{
        password: '',
        get minLength() { return this.password.length >= 8 },
        get hasLower() { return /[a-z]/.test(this.password) },
        get hasUpper() { return /[A-Z]/.test(this.password) },
        get hasSpecial() { return /[\p{P}\p{S}\p{Z}]/u.test(this.password) },
    }"
>
    <x-ui.label :for="$id">{{ $label }}</x-ui.label>
    <x-ui.input
        :id="$id"
        type="password"
        :name="$name"
        required
        autocomplete="{{ $autocomplete }}"
        x-model="password"
    />
    <x-ui.field-error name="password" />
    <x-ui.password-rules />
</div>
