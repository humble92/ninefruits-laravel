<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'विद्यार्थी नम्बर',
    'program' => 'कार्यक्रम',
    'bases' => [
        'credits' => 'प्राप्त क्रेडिट',
    ],
    'statuses' => [
        'draft' => 'मस्यौदा',
        'scheduled' => 'तालिकाबद्ध',
    ],
    'outcomes' => [
        'pending' => 'विचाराधीन',
    ],
]);
