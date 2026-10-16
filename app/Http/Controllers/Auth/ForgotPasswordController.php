<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LookupForgotPasswordRequest;
use App\Http\Requests\Auth\ResetForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'resetEmail' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function lookup(LookupForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->string('email')->toString();

        $user = User::query()
            ->where('email', $email)
            ->whereIn('role', [UserRole::Customer, UserRole::Driver])
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => 'We could not find a customer or driver account for that email.',
            ]);
        }

        $request->session()->put('password_reset_email', $user->email);

        return redirect()->route('password.forgot');
    }

    public function update(ResetForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! is_string($email) || $email === '') {
            return redirect()
                ->route('password.forgot')
                ->withErrors(['email' => 'Enter your email first.']);
        }

        $user = User::query()
            ->where('email', $email)
            ->whereIn('role', [UserRole::Customer, UserRole::Driver])
            ->first();

        if ($user === null) {
            $request->session()->forget('password_reset_email');

            throw ValidationException::withMessages([
                'email' => 'We could not find a customer or driver account for that email.',
            ]);
        }

        $user->update([
            'password' => $request->validated('password'),
        ]);

        $request->session()->forget('password_reset_email');
        $request->session()->regenerate();

        return redirect()
            ->route('login')
            ->with('status', 'Password updated. You can log in.');
    }
}
