@props([
    'paginator',
])

@php
    assert($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator);
@endphp

@if ($paginator->total() > 0)
    <div class="mt-4 flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-text-muted">
            @if ($paginator->total() === 1)
                Showing 1 result
            @else
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @endif
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-2">
                @if ($paginator->onFirstPage())
                    <span class="rounded-md border border-border px-3 py-1.5 text-sm text-text-subtle">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="rounded-md border border-border px-3 py-1.5 text-sm text-text hover:bg-surface-inset">Previous</a>
                @endif

                <span class="px-2 text-sm text-text-muted">
                    Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="rounded-md border border-border px-3 py-1.5 text-sm text-text hover:bg-surface-inset">Next</a>
                @else
                    <span class="rounded-md border border-border px-3 py-1.5 text-sm text-text-subtle">Next</span>
                @endif
            </div>
        @endif
    </div>
@endif
