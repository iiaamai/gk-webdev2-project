<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EarningsQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request, EarningsQuery $earningsQuery): View
    {
        abort_unless($request->user()?->isSystemAdmin(), 403);

        $now = now('Asia/Manila');
        $year = (int) $request->integer('year', $now->year);
        $month = (int) $request->integer('month', $now->month);

        $year = max(2020, min($now->year + 1, $year));
        $month = max(1, min(12, $month));

        $report = $earningsQuery->forMonth($year, $month);

        $yearOptions = range($now->year - 5, $now->year + 1);

        return view('admin.earnings.index', [
            'report' => $report,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'yearOptions' => $yearOptions,
        ]);
    }
}
