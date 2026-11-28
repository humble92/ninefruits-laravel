<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Nombor matrik',
    'bases' => [
        'credits' => 'Kredit diperoleh',
    ],
    'statuses' => [
        'draft' => 'Draf',
        'scheduled' => 'Dijadualkan',
    ],
    'outcomes' => [
        'pending' => 'Menunggu',
    ],
]);
