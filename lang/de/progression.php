<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Matrikelnummer',
    'program' => 'Studiengang',
    'bases' => [
        'credits' => 'Erworbene Leistungspunkte',
    ],
    'statuses' => [
        'draft' => 'Entwurf',
        'scheduled' => 'Geplant',
    ],
    'outcomes' => [
        'pending' => 'Offen',
    ],
    'all_programs' => 'Alle Studienprogramme',
    'review_program' => 'Studiengangsfilter (nur Anzeige)',
]);
