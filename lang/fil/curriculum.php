<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'asignatura',
            'plural_label' => 'mga asignatura',
        ],
        'course_versions' => [
            'label' => 'bersiyon ng asignatura',
            'plural_label' => 'mga bersiyon',
        ],
        'course_equivalencies' => [
            'label' => 'katumbas',
            'plural_label' => 'mga katumbas na asignatura',
        ],
        'requirement_sets' => [
            'label' => 'mga kinakailangan sa pagtatapos',
            'plural_label' => 'mga kinakailangan sa pagtatapos',
        ],
    ],
    'sections' => [
        'course' => 'Asignatura',
        'first_version' => 'Unang bersiyon',
        'requirements' => 'Mga kinakailangan',
    ],
    'fields' => [
        'assessment_type' => 'Uri ng pagsusulit',
        'code' => 'Code ng asignatura',
        'managing_unit' => 'Namamahalang yunit',
        'title' => 'Pamagat',
        'description' => 'Paglalarawan',
        'credits' => 'Units',
        'lecture_hours' => 'Oras ng lektyur',
        'lab_hours' => 'Oras ng laboratoryo',
        'category' => 'Kategorya ng asignatura',
        'grading_basis' => 'Pagmamarka',
        'counts_toward_credit_limit' => 'Ibinibilang sa limitasyon ng units',
        'is_repeatable' => 'Maaaring kunin muli para sa units',
        'requisites' => 'Mga requisite',
        'requisite_kind' => 'Uri',
        'group_number' => 'Grupo',
        'required_course' => 'Asignatura',
        'minimum_grade' => 'Pinakamababang marka',
        'allows_concurrent' => 'Maaaring kunin sa parehong termino',
        'effective_from' => 'Bagong bersiyon simula',
        'equivalent_course' => 'Katumbas na asignatura',
        'is_mutual' => 'Magkapalit',
        'note' => 'Tala',
        'program_role' => 'Papel sa programa',
        'catalog_year_from' => 'Taon ng kurikulum mula',
        'catalog_year_to' => 'Taon ng kurikulum hanggang',
        'total_credits' => 'Kabuuang units',
        'requirement_kind' => 'Kinakailangan',
        'min_credits' => 'Pinakamababang units',
        'min_courses' => 'Pinakamababang bilang ng asignatura',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'I-off para sa mga asignaturang labas sa limitasyon ng units bawat termino (hal. mga asignatura ng credit bank).',
        'is_repeatable' => 'Ang muling pagkuha nito ay nagbibigay ulit ng units (hal. mga seminar na nagbabago ang paksa) sa halip na ituring na retake.',
        'requisites' => 'Mga alternatibo ang mga asignatura sa parehong grupo (sapat na ang isa sa mga ito); dapat matugunan ang bawat grupo.',
        'revise' => 'Nagtatapos ang kasalukuyang bersiyon sa araw bago nito; nagsisimula ang bagong bersiyon bilang kopya nito at ng mga requisite nito. Pinapanatili ng mga seksiyong naialok na ang kanilang bersiyon.',
        'is_mutual' => 'Ibinibilang ang bawat isa para sa isa pa. Naka-off: ang katumbas na asignatura lamang ang ibinibilang para rito.',
        'catalog_year_to' => 'Blangko: para rin sa mga susunod na taon ng kurikulum hanggang magsimula ang ibang set.',
    ],
    'actions' => [
        'revise' => 'Rebisahin mula sa isang petsa',
        'add_requirement' => 'Magdagdag ng kinakailangan',
    ],
    'notifications' => [
        'revised' => 'Nagawa ang bagong bersiyon.',
    ],
    'errors' => [
        'no_version_to_revise' => 'Walang bersiyong may bisa sa petsang iyon.',
        'revise_same_start' => 'May bersiyon nang nagsisimula sa petsang iyon. Iyon na lang ang i-edit.',
        'version_overlap' => 'Nagsasapawan ang panahong ito at ang ibang bersiyon ng asignatura.',
        'requirement_set_overlap' => 'Nagsasapawan ang mga taon ng kurikulum na ito at ang ibang set ng kinakailangan ng programa at papel.',
    ],
    'import' => [
        'org_unit_required' => 'Kailangan ng mga bagong asignatura ang org_unit (code ng namamahalang yunit).',
    ],
    'or' => 'o',
    'grading_bases' => [
        'graded' => 'May marka',
        'pass_fail' => 'Pasado/bagsak',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Prerequisite',
        'corequisite' => 'Corequisite',
    ],
    'program_roles' => [
        'primary' => 'Pangunahing programa',
        'second_major' => 'Second major',
        'minor' => 'Minor',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Units sa isang kategorya',
        'required_courses' => 'Mga kinakailangang asignatura',
        'course_group' => 'Pagpili mula sa mga asignatura',
        'assessment_pass' => 'Pumasang pagsusulit',
    ],
];
