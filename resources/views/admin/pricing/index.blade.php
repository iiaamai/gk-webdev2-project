@extends('layouts.admin')

@section('title', 'Pricing')

@section('content')
    <x-ui.page-header title="Pricing list" subtitle="Vehicle types and payout amounts (PHP).">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.pricing.create') }}">
                <x-ui.icon name="plus" size="size-4" />
                Add pricing row
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($pricings->isEmpty())
        <x-ui.empty-state title="No pricing rows yet" icon="tags">
            <x-slot:description>Add at least one vehicle type amount before customers can book.</x-slot:description>
            <x-slot:actions>
                <x-ui.button href="{{ route('admin.pricing.create') }}">
                    <x-ui.icon name="plus" size="size-4" />
                    Add pricing row
                </x-ui.button>
            </x-slot:actions>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-medium">Vehicle type</th>
                    <th class="px-4 py-3 font-medium">Amount (PHP)</th>
                    <th class="px-4 py-3 font-medium">Capacity (kg)</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @foreach ($pricings as $pricing)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $pricing->vehicle_type }}</td>
                    <td class="px-4 py-3">₱{{ number_format((float) $pricing->amount, 2) }}</td>
                    <td class="px-4 py-3">{{ $pricing->capacity_kg }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-ui.button href="{{ route('admin.pricing.edit', $pricing) }}" variant="secondary" class="!py-1.5 !text-xs">
                                <x-ui.icon name="pencil" size="size-3.5" />
                                Edit
                            </x-ui.button>
                            <form method="post" action="{{ route('admin.pricing.destroy', $pricing) }}" onsubmit="return confirm('Archive this pricing row?');">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" class="!py-1.5 !text-xs !text-danger">
                                    <x-ui.icon name="archive" size="size-3.5" />
                                    Archive
                                </x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
@endsection
