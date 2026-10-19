@php
    $layout = \App\Support\ErrorPage::layout(request());
    $homeUrl = \App\Support\ErrorPage::homeUrl(request());
    $isAuth = $layout === 'layouts.auth';
@endphp

@extends($layout)

@section('title', $title)

@section('content')
    @if ($isAuth)
        <div class="text-center">
            @if (! empty($code))
                <p class="text-sm font-medium uppercase tracking-wide text-text-subtle">{{ $code }}</p>
            @endif
            <h1 class="mt-2 text-xl font-semibold text-text">{{ $heading }}</h1>
            <p class="mt-3 text-sm text-text-muted">{{ $message }}</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <x-ui.button href="{{ $homeUrl }}" type="button">Back to home</x-ui.button>
            </div>
        </div>
    @else
        <x-ui.card class="mx-auto max-w-lg text-center">
            @if (! empty($code))
                <p class="text-sm font-medium uppercase tracking-wide text-text-subtle">{{ $code }}</p>
            @endif
            <h1 class="mt-2 text-2xl font-semibold text-text">{{ $heading }}</h1>
            <p class="mt-3 text-sm text-text-muted">{{ $message }}</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <x-ui.button href="{{ $homeUrl }}" type="button">Back to home</x-ui.button>
            </div>
        </x-ui.card>
    @endif
@endsection
