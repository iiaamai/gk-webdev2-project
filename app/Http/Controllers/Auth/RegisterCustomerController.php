<?php

namespace App\Http\Controllers\Auth;

use App\Actions\StartPendingRegistration;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Support\RoleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterCustomerController extends Controller
{
    public function create(): View
    {
        return view('auth.register-customer');
    }

    public function store(
        RegisterCustomerRequest $request,
        StartPendingRegistration $startPendingRegistration,
    ): RedirectResponse {
        $user = $startPendingRegistration->execute(
            $request->safe()->only(['name', 'email', 'mobile', 'password']),
            UserRole::Customer,
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
