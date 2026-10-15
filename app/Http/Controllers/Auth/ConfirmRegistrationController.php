<?php

namespace App\Http\Controllers\Auth;

use App\Actions\CompletePendingRegistration;
use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Support\RoleHome;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConfirmRegistrationController extends Controller
{
    public function __invoke(
        Request $request,
        PendingRegistration $pendingRegistration,
        CompletePendingRegistration $completePendingRegistration,
    ): RedirectResponse {
        try {
            $user = $completePendingRegistration->execute($pendingRegistration);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('login')
                ->withErrors($exception->errors());
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to(RoleHome::path($user));
    }
}
