<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Оюутны код',
    'program' => 'Хөтөлбөр',
    'bases' => [
        'credits' => 'Цуглуулсан кредит',
    ],
    'statuses' => [
        'draft' => 'Ноорог',
        'scheduled' => 'Товлосон',
    ],
    'outcomes' => [
        'pending' => 'Хүлээгдэж буй',
    ],
    'all_programs' => 'Бүх хөтөлбөр',
    'review_program' => 'Хөтөлбөрийн шүүлтүүр (зөвхөн харагдац)',
]);
