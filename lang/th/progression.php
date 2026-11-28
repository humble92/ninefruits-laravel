<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'รหัสนักศึกษา',
    'program' => 'หลักสูตร',
    'bases' => [
        'credits' => 'หน่วยกิตที่ได้',
    ],
    'statuses' => [
        'draft' => 'ฉบับร่าง',
        'scheduled' => 'ตามกำหนด',
    ],
    'outcomes' => [
        'pending' => 'รอพิจารณา',
    ],
    'all_programs' => 'ทุกหลักสูตร',
    'review_program' => 'ตัวกรองหลักสูตร (เฉพาะการแสดงผล)',
]);
