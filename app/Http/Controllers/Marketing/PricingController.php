<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(): View
    {
        $pricings = Pricing::query()
            ->orderBy('vehicle_type')
            ->get();

        return view('marketing.pricing', [
            'pricings' => $pricings,
        ]);
    }
}
