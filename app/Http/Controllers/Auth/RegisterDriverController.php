<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterDriverRequest;
use App\Models\User;
use App\Support\MailIntegration;
use App\Support\RoleHome;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterDriverController extends Controller
{
    public function create(): View
    {
        return view('auth.register-driver');
    }

    public function store(RegisterDriverRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            ...$request->safe()->only([
                'name',
                'email',
                'mobile',
                'password',
            ]),
            'role' => UserRole::Driver,
            'email_verified_at' => MailIntegration::isEnabled() ? null : now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->to(RoleHome::path($user));
    }
}
