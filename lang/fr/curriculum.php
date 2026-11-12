<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'cours',
            'plural_label' => 'cours',
        ],
        'course_versions' => [
            'label' => 'version du cours',
            'plural_label' => 'versions',
        ],
        'course_equivalencies' => [
            'label' => 'équivalence',
            'plural_label' => 'cours équivalents',
        ],
        'requirement_sets' => [
            'label' => 'conditions d’obtention du diplôme',
            'plural_label' => 'conditions d’obtention du diplôme',
        ],
    ],
    'sections' => [
        'course' => 'Cours',
        'first_version' => 'Première version',
        'requirements' => 'Conditions',
    ],
    'fields' => [
        'assessment_type' => 'Type d’examen',
        'code' => 'Code du cours',
        'managing_unit' => 'Unité gestionnaire',
        'title' => 'Intitulé',
        'description' => 'Description',
        'credits' => 'Crédits',
        'lecture_hours' => 'Heures de cours magistral',
        'lab_hours' => 'Heures de TP',
        'category' => 'Catégorie de cours',
        'grading_basis' => 'Notation',
        'counts_toward_credit_limit' => 'Compte dans le plafond de crédits',
        'is_repeatable' => 'Peut être suivi à nouveau pour des crédits',
        'requisites' => 'Conditions préalables',
        'requisite_kind' => 'Type',
        'group_number' => 'Groupe',
        'required_course' => 'Cours',
        'minimum_grade' => 'Note minimale',
        'allows_concurrent' => 'Peut être suivi le même semestre',
        'effective_from' => 'Nouvelle version à partir du',
        'equivalent_course' => 'Cours équivalent',
        'is_mutual' => 'Réciproque',
        'note' => 'Remarque',
        'program_role' => 'Rôle dans le cursus',
        'catalog_year_from' => 'Année de catalogue (début)',
        'catalog_year_to' => 'Année de catalogue (fin)',
        'total_credits' => 'Total des crédits',
        'requirement_kind' => 'Condition',
        'min_credits' => 'Crédits minimum',
        'min_courses' => 'Nombre minimum de cours',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'À désactiver pour les cours hors du plafond de crédits du semestre (p. ex. cours de banque de crédits).',
        'is_repeatable' => 'Le suivre à nouveau rapporte de nouveaux crédits (p. ex. séminaires à thème variable) au lieu d’être une reprise.',
        'requisites' => 'Les cours d’un même groupe sont des alternatives (un seul suffit) ; chaque groupe doit être satisfait.',
        'revise' => 'La version actuelle se termine la veille ; la nouvelle version commence comme une copie de celle-ci et de ses conditions préalables. Les groupes déjà ouverts conservent leur version.',
        'is_mutual' => 'Chacun compte pour l’autre. Désactivé : seul le cours équivalent compte pour celui-ci.',
        'catalog_year_to' => 'Vide : vaut aussi pour les années de catalogue suivantes jusqu’au début d’un autre ensemble.',
    ],
    'actions' => [
        'revise' => 'Réviser à partir d’une date',
        'add_requirement' => 'Ajouter une condition',
    ],
    'notifications' => [
        'revised' => 'Nouvelle version créée.',
    ],
    'errors' => [
        'no_version_to_revise' => 'Aucune version n’est en vigueur à cette date.',
        'revise_same_start' => 'Une version commence déjà à cette date. Modifiez-la plutôt.',
        'version_overlap' => 'Cette période chevauche une autre version du cours.',
        'requirement_set_overlap' => 'Ces années de catalogue chevauchent un autre ensemble de conditions de la formation et du rôle.',
    ],
    'import' => [
        'org_unit_required' => 'Les nouveaux cours nécessitent un org_unit (code de l’unité gestionnaire).',
    ],
    'or' => 'ou',
    'grading_bases' => [
        'graded' => 'Note littérale',
        'pass_fail' => 'Réussi/échoué',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Prérequis',
        'corequisite' => 'Corequis',
    ],
    'program_roles' => [
        'primary' => 'Formation principale',
        'second_major' => 'Double majeure',
        'minor' => 'Mineure',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Crédits dans une catégorie',
        'required_courses' => 'Cours obligatoires',
        'course_group' => 'Choix parmi des cours',
        'assessment_pass' => 'Examen réussi',
    ],
];
