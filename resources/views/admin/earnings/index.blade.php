@extends('layouts.admin')

@section('title', 'Earnings')

@push('head')
    @vite('resources/js/admin-earnings.js')
@endpush

@section('content')
    <div id="earnings-report" class="earnings-report">
        <x-ui.page-header
            title="Earnings"
            subtitle="Completed trips and invoice revenue for the selected month (Asia/Manila)."
        >
            <x-slot:actions>
                <x-ui.button type="button" variant="secondary" class="print:hidden" onclick="window.print()">
                    <x-ui.icon name="file-up" size="size-4" />
                    Export / print PDF
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <form
            method="get"
            action="{{ route('admin.earnings.index') }}"
            class="print:hidden mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-border bg-surface-elevated p-4"
        >
            <div>
                <x-ui.label for="month">Month</x-ui.label>
                <x-ui.select id="month" name="month" class="mt-1 min-w-[10rem]">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($selectedMonth === $m)>
                            {{ \Illuminate\Support\Carbon::create(null, $m, 1)->format('F') }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
            <div>
                <x-ui.label for="year">Year</x-ui.label>
                <x-ui.select id="year" name="year" class="mt-1 min-w-[8rem]">
                    @foreach ($yearOptions as $y)
                        <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <x-ui.button type="submit">Apply</x-ui.button>
        </form>

        <p class="mb-4 text-sm font-medium text-text print:mb-2">
            Period: {{ $report['period_label'] }}
        </p>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card>
                <p class="text-sm text-text-muted">Completed trips</p>
                <p class="mt-1 text-2xl font-semibold text-text">{{ $report['kpis']['completed_trips'] }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-text-muted">Completed revenue (invoiced)</p>
                <p class="mt-1 text-2xl font-semibold text-text">₱{{ number_format((float) $report['kpis']['completed_revenue'], 2) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-text-muted">Paid revenue (collected)</p>
                <p class="mt-1 text-2xl font-semibold text-text">₱{{ number_format((float) $report['kpis']['paid_revenue'], 2) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-text-muted">Average completed payout</p>
                <p class="mt-1 text-2xl font-semibold text-text">₱{{ number_format((float) $report['kpis']['average_completed_payout'], 2) }}</p>
            </x-ui.card>
        </div>

        @if (! $report['has_data'])
            <x-ui.empty-state title="No earnings in this period" icon="chart-column">
                <x-slot:description>Try another month or year, or complete trips with invoices first.</x-slot:description>
            </x-ui.empty-state>
        @else
            <script id="earnings-chart-data" type="application/json">{!! json_encode([
                'labels' => $report['labels'],
                'daily' => $report['daily'],
                'has_data' => $report['has_data'],
            ], JSON_THROW_ON_ERROR) !!}</script>

            <div class="grid gap-6 lg:grid-cols-2 print:grid-cols-1 print:gap-4">
                <x-ui.card>
                    <x-ui.section-heading
                        icon="chart-column"
                        title="Payout by day"
                        description="Invoiced (completed) vs paid (collected by paid date)."
                    />
                    <div class="mt-4 h-72 print:h-64">
                        <canvas id="earnings-payout-chart" aria-label="Daily payout chart"></canvas>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.section-heading
                        icon="chart-column"
                        title="Completed trips by day"
                        description="Trips completed in this month (by accept / create date)."
                    />
                    <div class="mt-4 h-72 print:h-64">
                        <canvas id="earnings-trips-chart" aria-label="Daily completed trips chart"></canvas>
                    </div>
                </x-ui.card>
            </div>

            <div class="print:hidden mt-6 rounded-lg border border-border bg-surface-inset p-4 text-sm text-text-muted">
                <p class="font-medium text-text">Report summary</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Completed revenue uses invoice amounts for trips completed in {{ $report['period_label'] }}.</li>
                    <li>Paid revenue uses invoices marked paid with <code class="text-xs">paid_at</code> in this month.</li>
                    <li>Use <strong>Export / print PDF</strong> and choose &ldquo;Save as PDF&rdquo; in the print dialog.</li>
                </ul>
            </div>
        @endif
    </div>

    <style>
        @media print {
            .earnings-report canvas {
                max-height: 14rem !important;
            }
        }
    </style>
@endsection
