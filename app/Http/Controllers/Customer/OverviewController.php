<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerOverviewQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, CustomerOverviewQuery $customerOverviewQuery): View
    {
        abort_unless($request->user()?->isCustomer(), 403);

        $customer = $request->user();
        $overview = $customerOverviewQuery->gather($customer);

        return view('portals.customer-home', [
            'name' => $customer->name,
            'overview' => $overview,
            'generatedAt' => $customerOverviewQuery->nowInManila(),
        ]);
    }
}
