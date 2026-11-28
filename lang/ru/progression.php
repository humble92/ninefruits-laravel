<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Студенческий номер',
    'program' => 'Программа',
    'bases' => [
        'credits' => 'Полученные кредиты',
    ],
    'statuses' => [
        'draft' => 'Черновик',
        'scheduled' => 'Запланировано',
    ],
    'outcomes' => [
        'pending' => 'Ожидает',
    ],
]);
