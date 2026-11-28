<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'student' => 'Numéro d’étudiant',
    'program' => 'Formation',
    'bases' => [
        'credits' => 'Crédits acquis',
    ],
    'statuses' => [
        'draft' => 'Brouillon',
        'scheduled' => 'Prévue',
    ],
    'outcomes' => [
        'pending' => 'En attente',
    ],
    'all_programs' => 'Tous les programmes',
    'review_program' => 'Filtre par programme (affichage uniquement)',
]);
