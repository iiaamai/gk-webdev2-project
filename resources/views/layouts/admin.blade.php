<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — GK Trucking Services</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; }
        header { background: #1e293b; color: #fff; padding: 0.75rem 1rem; }
        header a { color: #e2e8f0; margin-right: 1rem; text-decoration: none; }
        header a:hover { color: #fff; }
        main { max-width: 56rem; margin: 1.5rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #e2e8f0; padding: 0.5rem; text-align: left; }
        label { display: block; margin-top: 0.75rem; }
        input, select { width: 100%; max-width: 24rem; padding: 0.4rem; box-sizing: border-box; }
        button, .btn { display: inline-block; margin-top: 1rem; padding: 0.4rem 0.75rem; cursor: pointer; }
        .error { color: #b91c1c; }
        .status { color: #047857; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <header>
        <strong>GK Admin</strong>
        <nav>
            <a href="{{ route('admin.home') }}">Overview</a>
            <a href="{{ route('admin.settings.edit') }}">Settings</a>
            <a href="{{ route('admin.pricing.index') }}">Pricing</a>
            <a href="{{ route('admin.fleet.index') }}">Fleet</a>
            <a href="{{ route('admin.users.index') }}">Users</a>
            <a href="{{ route('admin.bookings.index') }}">Bookings</a>
            <a href="{{ route('admin.earnings.index') }}">Earnings</a>
            <a href="{{ route('admin.activity-logs.index') }}">Activity logs</a>
        </nav>
        <form method="post" action="{{ route('logout') }}" style="display:inline;margin-left:1rem;">
            @csrf
            <button type="submit" style="background:transparent;border:1px solid #94a3b8;color:#e2e8f0;">Log out</button>
        </form>
    </header>
    <main>
        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul class="error">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        @yield('content')
    </main>
</body>
</html>
