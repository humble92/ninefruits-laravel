<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Talaba raqami',
    'program' => 'Taʼlim dasturi',
    'bases' => [
        'credits' => 'Toʻplangan kreditlar',
    ],
    'statuses' => [
        'draft' => 'Qoralama',
        'scheduled' => 'Rejalashtirilgan',
    ],
    'outcomes' => [
        'pending' => 'Kutilmoqda',
    ],
]);
