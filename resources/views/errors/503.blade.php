@include('errors.partials.frame', [
    'title' => 'Service unavailable',
    'code' => '503',
    'heading' => 'The site is temporarily unavailable',
    'message' => 'Please try again in a few minutes.',
])
