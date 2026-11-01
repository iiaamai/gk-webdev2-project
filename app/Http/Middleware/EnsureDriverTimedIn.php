<?php

namespace App\Http\Middleware;

use App\Support\DriverAttendance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverTimedIn
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isDriver()) {
            return $next($request);
        }

        if (DriverAttendance::isTimedIn($user)) {
            return $next($request);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => DriverAttendance::sessionEndedMessage(),
            ]);
    }
}
