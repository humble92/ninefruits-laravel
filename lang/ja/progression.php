<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => '学籍番号',
    'program' => 'プログラム',
    'bases' => [
        'credits' => '修得単位',
    ],
    'statuses' => [
        'draft' => '下書き',
        'scheduled' => '予定',
    ],
    'outcomes' => [
        'pending' => '承認待ち',
    ],
    'all_programs' => 'すべてのプログラム',
    'review_program' => 'プログラムで絞り込み（表示のみ）',
]);
