<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePricingRequest;
use App\Http\Requests\Admin\UpdatePricingRequest;
use App\Models\Pricing;
use App\Support\ListFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Pricing::class);

        $search = ListFilter::searchTerm($request);
        $filtersActive = ListFilter::isActive($request, ['q']);

        $pricings = Pricing::query()
            ->when($search !== '', fn ($query) => $query->where('vehicle_type', 'like', '%'.$search.'%'))
            ->orderBy('vehicle_type')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();

        return view('admin.pricing.index', [
            'pricings' => $pricings,
            'filtersActive' => $filtersActive,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Pricing::class);

        return view('admin.pricing.create');
    }

    public function store(StorePricingRequest $request): RedirectResponse
    {
        Pricing::query()->create($request->validated());

        return redirect()
            ->route('admin.pricing.index')
            ->with('status', 'Pricing row created.');
    }

    public function edit(Pricing $pricing): View
    {
        $this->authorize('update', $pricing);

        return view('admin.pricing.edit', compact('pricing'));
    }

    public function update(UpdatePricingRequest $request, Pricing $pricing): RedirectResponse
    {
        $pricing->update($request->validated());

        return redirect()
            ->route('admin.pricing.index')
            ->with('status', 'Pricing row updated.');
    }

    public function destroy(Pricing $pricing): RedirectResponse
    {
        $this->authorize('delete', $pricing);

        $pricing->archive();

        return redirect()
            ->route('admin.pricing.index')
            ->with('status', 'Pricing row archived.');
    }
}
