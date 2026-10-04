<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ucfirst($role) }} portal — GK Trucking Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; padding: 0 1rem; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; }
        [x-cloak]{display:none!important}
    </style>
</head>
<body>
    <h1>GK Trucking Services</h1>
    <p>Signed in as <strong>{{ $name }}</strong> ({{ $role }}).</p>
    <p>Portal shell placeholder — full layout arrives in F1.</p>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <div x-data="{ open: false }">
        <form x-ref="logoutForm" method="post" action="{{ route('logout') }}">
            @csrf
            <button type="button" @click="open = true">Log out</button>
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
</body>
</html>
