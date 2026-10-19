@include('errors.partials.frame', [
    'title' => 'Too many requests',
    'code' => '429',
    'heading' => 'Too many requests',
    'message' => 'Please wait a moment and try again.',
])
