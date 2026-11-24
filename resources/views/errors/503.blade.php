@include('errors.notice', [
    'code' => 503,
    'title' => __('errors.unavailable.title'),
    'message' => __('errors.unavailable.message'),
])
