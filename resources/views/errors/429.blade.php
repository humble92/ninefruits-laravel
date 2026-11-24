@include('errors.notice', [
    'code' => 429,
    'title' => __('errors.too_many_requests.title'),
    'message' => __('errors.too_many_requests.message'),
])
