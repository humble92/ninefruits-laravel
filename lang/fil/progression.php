<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'program' => 'Programa',
    'bases' => [
        'credits' => 'Nakuhang units',
    ],
    'statuses' => [
        'scheduled' => 'Nakaiskedyul',
    ],
    'outcomes' => [
        'pending' => 'Nakabinbin',
    ],
    'all_programs' => 'Lahat ng programa',
    'review_program' => 'Salain ayon sa programa (display lamang)',
]);
