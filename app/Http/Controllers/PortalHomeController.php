<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalHomeController extends Controller
{
    /**
     * Temporary portal landing until F1 layout shells.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isCustomer()) {
            return view('portals.customer-home', [
                'name' => $user->name,
            ]);
        }

        if ($user->isStaff()) {
            return view('portals.staff-home', [
                'name' => $user->name,
            ]);
        }

        return view('portals.home', [
            'role' => $user->role->value,
            'name' => $user->name,
        ]);
    }
}
