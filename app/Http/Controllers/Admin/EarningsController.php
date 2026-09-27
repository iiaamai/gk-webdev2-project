<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EarningsQuery;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(EarningsQuery $earningsQuery): View
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $summary = $earningsQuery->summary();

        return view('admin.earnings.index', [
            'totalCompleted' => $summary['total_completed'],
            'totalPayout' => $summary['total_payout'],
            'averagePayout' => $summary['average_payout'],
            'monthly' => $summary['monthly'],
        ]);
    }
}
