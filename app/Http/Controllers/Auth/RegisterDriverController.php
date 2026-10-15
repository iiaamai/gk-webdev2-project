<?php

namespace App\Http\Controllers\Auth;

use App\Actions\StartPendingRegistration;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterDriverRequest;
use App\Support\RoleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterDriverController extends Controller
{
    public function create(): View
    {
        return view('auth.register-driver');
    }

    public function store(
        RegisterDriverRequest $request,
        StartPendingRegistration $startPendingRegistration,
    ): RedirectResponse {
        $user = $startPendingRegistration->execute(
            $request->safe()->only(['name', 'email', 'mobile', 'password']),
            UserRole::Driver,
        );

        if ($user === null) {
            return redirect()
                ->route('register.check')
                ->with('status', 'We sent a confirmation link to '.$request->string('email')->toString().'.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to(RoleHome::path($user));
    }
}
