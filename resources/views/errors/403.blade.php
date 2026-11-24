@include('errors.notice', [
    'code' => 403,
    'title' => __('errors.forbidden.title'),
    'message' => __('errors.forbidden.message'),
])
