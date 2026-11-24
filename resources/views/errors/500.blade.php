@include('errors.notice', [
    'code' => 500,
    'title' => __('errors.server_error.title'),
    'message' => __('errors.server_error.message'),
])
