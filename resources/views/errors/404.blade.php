@include('errors.notice', [
    'code' => 404,
    'title' => __('errors.not_found.title'),
    'message' => __('errors.not_found.message'),
])
