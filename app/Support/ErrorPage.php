<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;

class ErrorPage
{
    public static function layout(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User) {
            return match ($user->role) {
                UserRole::Customer => 'layouts.customer',
                UserRole::Driver => 'layouts.driver',
                UserRole::Staff => 'layouts.staff',
                UserRole::SystemAdmin => 'layouts.admin',
            };
        }

        if ($request->is('customer', 'customer/*')) {
            return 'layouts.customer';
        }

        if ($request->is('driver', 'driver/*')) {
            return 'layouts.driver';
        }

        if ($request->is('staff', 'staff/*')) {
            return 'layouts.staff';
        }

        if ($request->is('admin', 'admin/*')) {
            return 'layouts.admin';
        }

        if ($request->is('login', 'register*', 'password/*', 'forgot-password', 'email/*', 'verify-email*')) {
            return 'layouts.auth';
        }

        return 'layouts.marketing';
    }

    public static function homeUrl(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User) {
            return RoleHome::path($user);
        }

        if ($request->is('login', 'register*', 'password/*', 'forgot-password', 'email/*', 'verify-email*')) {
            return route('login');
        }

        return route('home');
    }
}
