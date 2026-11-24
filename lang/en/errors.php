<?php

return [
    'forbidden' => [
        'title' => 'Access restricted',
        'message' => 'Your account does not have permission to open this page or view this information.',
    ],
    'not_found' => [
        'title' => 'Page or information unavailable',
        'message' => 'This page may have moved, or the requested information may be unavailable to your account.',
    ],
    'certificate_pdf' => [
        'title' => 'Certificate PDF unavailable',
        'message' => 'The certificate record is available, but its PDF file could not be opened.',
    ],
    'page_expired' => [
        'title' => 'Page expired',
        'message' => 'This page was open for a long time or your sign-in changed, so the request could not be completed. Go back, refresh the page and try again.',
        'reload_prompt' => 'Refresh the page? Anything you have not saved will be lost.',
    ],
    'too_many_requests' => [
        'title' => 'Too many requests',
        'message' => 'Too many requests were sent in a short time. Please wait a moment and try again.',
    ],
    'server_error' => [
        'title' => 'Something went wrong',
        'message' => 'A problem occurred while processing your request. If it continues, contact the office in charge with the reference number below.',
    ],
    'unavailable' => [
        'title' => 'Temporarily unavailable',
        'message' => 'The service is under maintenance or temporarily unavailable. Please try again shortly.',
    ],
    'code' => 'Error :code',
    'reference' => 'Reference',
];
