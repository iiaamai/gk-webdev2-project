<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\StaffOverviewQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, StaffOverviewQuery $staffOverviewQuery): View
    {
        abort_unless($request->user()?->isStaff(), 403);

        $overview = $staffOverviewQuery->gather();

        return view('portals.staff-home', [
            'name' => $request->user()->name,
            'overview' => $overview,
            'generatedAt' => $staffOverviewQuery->nowInManila(),
        ]);
    }
}
