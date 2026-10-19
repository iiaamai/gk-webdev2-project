@include('errors.partials.frame', [
    'title' => 'Session expired',
    'code' => '419',
    'heading' => 'Your session expired',
    'message' => 'Please refresh the page and try again. If you were filling a form, sign in again first.',
])
