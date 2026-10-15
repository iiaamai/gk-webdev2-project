<x-mail::message>
# Confirm your account

Hello {{ $pending->name }},

We received a request to create a {{ $pending->role->label() }} account for **{{ $pending->email }}**.

Your account is not created until you confirm this email.

<x-mail::button :url="$confirmUrl">
Confirm and create account
</x-mail::button>

This link expires in 24 hours. If you did not request this, you can ignore the email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
