@if (session('status'))
    <div class="mb-4 rounded-md border border-success/20 bg-success-subtle px-4 py-3 text-sm text-success" role="status">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-md border border-danger/20 bg-danger-subtle px-4 py-3 text-sm text-danger" role="alert">
        <ul class="list-disc space-y-1 ps-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
