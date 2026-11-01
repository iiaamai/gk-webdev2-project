<?php

namespace App\Http\Controllers;

use App\Actions\TimeInDriver;
use App\Actions\TimeOutDriver;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverAttendanceController extends Controller
{
    public function timeIn(
        Request $request,
        User $user,
        TimeInDriver $timeInDriver,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        abort_unless($user->isDriver(), 404);

        $this->authorize('timeIn', $user);

        try {
            $timeInDriver->execute($user, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors());
        }

        $activityLogger->log(
            action: 'driver.timed_in',
            subject: $user,
            description: "Driver {$user->email} timed in.",
            request: $request,
        );

        return redirect()
            ->back()
            ->with('status', "{$user->name} is now timed in.");
    }

    public function timeOut(
        Request $request,
        User $user,
        TimeOutDriver $timeOutDriver,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        abort_unless($user->isDriver(), 404);

        $this->authorize('timeOut', $user);

        try {
            $timeOutDriver->execute($user, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors());
        }

        $activityLogger->log(
            action: 'driver.timed_out',
            subject: $user,
            description: "Driver {$user->email} timed out.",
            request: $request,
        );

        return redirect()
            ->back()
            ->with('status', "{$user->name} is now timed out.");
    }
}
