<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'course',
            'plural_label' => 'courses',
        ],
        'course_versions' => [
            'label' => 'course version',
            'plural_label' => 'versions',
        ],
        'course_equivalencies' => [
            'label' => 'equivalency',
            'plural_label' => 'equivalent courses',
        ],
        'requirement_sets' => [
            'label' => 'graduation requirements',
            'plural_label' => 'graduation requirements',
        ],
    ],
    'sections' => [
        'course' => 'Course',
        'first_version' => 'First version',
        'requirements' => 'Requirements',
    ],
    'fields' => [
        'assessment_type' => 'Examination kind',
        'code' => 'Course code',
        'managing_unit' => 'Managing unit',
        'title' => 'Title',
        'description' => 'Description',
        'credits' => 'Credits',
        'lecture_hours' => 'Lecture hours',
        'lab_hours' => 'Lab hours',
        'category' => 'Course category',
        'grading_basis' => 'Grading',
        'counts_toward_credit_limit' => 'Counts toward the credit limit',
        'is_repeatable' => 'May be taken again for credit',
        'requisites' => 'Requisites',
        'requisite_kind' => 'Kind',
        'group_number' => 'Group',
        'required_course' => 'Course',
        'minimum_grade' => 'Minimum grade',
        'allows_concurrent' => 'May be taken in the same term',
        'effective_from' => 'New version from',
        'equivalent_course' => 'Equivalent course',
        'is_mutual' => 'Mutual',
        'note' => 'Note',
        'program_role' => 'Program role',
        'catalog_year_from' => 'Catalog year from',
        'catalog_year_to' => 'Catalog year to',
        'total_credits' => 'Total credits',
        'requirement_kind' => 'Requirement',
        'min_credits' => 'Minimum credits',
        'min_courses' => 'Minimum courses',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'Turn off for courses outside the term credit limit (e.g. credit bank courses).',
        'is_repeatable' => 'Taking it again earns credits again (e.g. seminars with changing topics) instead of being a retake.',
        'requisites' => 'Courses in the same group are alternatives (one of them is enough); every group must be met.',
        'revise' => 'The current version ends the day before; the new version starts with a copy of it and its requisites. Sections already offered keep their version.',
        'is_mutual' => 'Each counts for the other. Off: only the equivalent course counts for this one.',
        'catalog_year_to' => 'Empty: also for later catalog years until another set starts.',
    ],
    'actions' => [
        'revise' => 'Revise from a date',
        'add_requirement' => 'Add requirement',
    ],
    'notifications' => [
        'revised' => 'New version created.',
    ],
    'errors' => [
        'no_version_to_revise' => 'There is no version in effect on that date.',
        'revise_same_start' => 'A version already starts on that date. Edit it instead.',
        'version_overlap' => 'This period overlaps another version of the course.',
        'requirement_set_overlap' => 'These catalog years overlap another requirement set of the program and role.',
    ],
    'import' => [
        'org_unit_required' => 'New courses need an org_unit (code of the managing unit).',
    ],
    'or' => 'or',
    'grading_bases' => [
        'graded' => 'Letter grade',
        'pass_fail' => 'Pass/fail',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Prerequisite',
        'corequisite' => 'Corequisite',
    ],
    'program_roles' => [
        'primary' => 'Primary program',
        'second_major' => 'Second major',
        'minor' => 'Minor',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Credits in a category',
        'required_courses' => 'Required courses',
        'course_group' => 'Choice from courses',
        'assessment_pass' => 'Passed examination',
    ],
];
