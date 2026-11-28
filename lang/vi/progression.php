<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Mã số sinh viên',
    'program' => 'Chương trình đào tạo',
    'bases' => [
        'credits' => 'Tín chỉ tích lũy',
    ],
    'statuses' => [
        'draft' => 'Bản nháp',
        'scheduled' => 'Theo lịch',
    ],
    'outcomes' => [
        'pending' => 'Chờ duyệt',
    ],
    'all_programs' => 'Tất cả chương trình',
    'review_program' => 'Bộ lọc chương trình (chỉ để hiển thị)',
]);
