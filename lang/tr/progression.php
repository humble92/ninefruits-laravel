<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Öğrenci numarası',
    'bases' => [
        'credits' => 'Kazanılan kredi',
    ],
    'statuses' => [
        'draft' => 'Taslak',
        'scheduled' => 'Planlandı',
    ],
    'outcomes' => [
        'pending' => 'Beklemede',
    ],
    'all_programs' => 'Tüm programlar',
    'review_program' => 'Program filtresi (yalnızca görünüm)',
]);
