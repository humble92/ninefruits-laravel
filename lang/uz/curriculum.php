<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'fan',
            'plural_label' => 'fanlar',
        ],
        'course_versions' => [
            'label' => 'fan versiyasi',
            'plural_label' => 'versiyalar',
        ],
        'course_equivalencies' => [
            'label' => 'muqobillik',
            'plural_label' => 'muqobil fanlar',
        ],
        'requirement_sets' => [
            'label' => 'bitiruv talablari',
            'plural_label' => 'bitiruv talablari',
        ],
    ],
    'sections' => [
        'course' => 'Fan',
        'first_version' => 'Birinchi versiya',
        'requirements' => 'Talablar',
    ],
    'fields' => [
        'assessment_type' => 'Imtihon turi',
        'code' => 'Fan kodi',
        'managing_unit' => 'Masʼul boʻlinma',
        'title' => 'Nomi',
        'description' => 'Tavsif',
        'credits' => 'Kreditlar',
        'lecture_hours' => 'Maʼruza soatlari',
        'lab_hours' => 'Laboratoriya soatlari',
        'category' => 'Fan toifasi',
        'grading_basis' => 'Baholash',
        'counts_toward_credit_limit' => 'Kredit chegarasiga hisoblanadi',
        'is_repeatable' => 'Kredit uchun qayta oʻqish mumkin',
        'requisites' => 'Rekvizitlar',
        'requisite_kind' => 'Tur',
        'group_number' => 'Guruh',
        'required_course' => 'Fan',
        'minimum_grade' => 'Minimal baho',
        'allows_concurrent' => 'Shu semestrda birga oʻqish mumkin',
        'effective_from' => 'Yangi versiya boshlanishi',
        'equivalent_course' => 'Muqobil fan',
        'is_mutual' => 'Oʻzaro',
        'note' => 'Izoh',
        'program_role' => 'Dasturdagi oʻrni',
        'catalog_year_from' => 'Oʻquv reja yilidan',
        'catalog_year_to' => 'Oʻquv reja yiligacha',
        'total_credits' => 'Jami kreditlar',
        'requirement_kind' => 'Talab',
        'min_credits' => 'Minimal kreditlar',
        'min_courses' => 'Minimal fanlar soni',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'Semestr kredit chegarasidan tashqaridagi fanlar uchun oʻchiring (masalan, kredit banki fanlari).',
        'is_repeatable' => 'Qayta oʻqilganda qayta oʻqish hisoblanmasdan yana kredit beradi (masalan, mavzusi oʻzgaruvchi seminarlar).',
        'requisites' => 'Bir guruhdagi fanlar muqobil (bittasi yetarli); har bir guruh bajarilishi kerak.',
        'revise' => 'Joriy versiya bir kun oldin tugaydi; yangi versiya uning va rekvizitlarining nusxasi bilan boshlanadi. Allaqachon ochilgan fan guruhlari oʻz versiyasini saqlaydi.',
        'is_mutual' => 'Har biri ikkinchisi oʻrniga hisoblanadi. Oʻchirilgan: faqat muqobil fan bu fan oʻrniga hisoblanadi.',
        'catalog_year_to' => 'Boʻsh: boshqa toʻplam boshlanguncha keyingi oʻquv reja yillari uchun ham.',
    ],
    'actions' => [
        'revise' => 'Sanadan boshlab qayta koʻrib chiqish',
        'add_requirement' => 'Talab qoʻshish',
    ],
    'notifications' => [
        'revised' => 'Yangi versiya yaratildi.',
    ],
    'errors' => [
        'no_version_to_revise' => 'Bu sanada amaldagi versiya yoʻq.',
        'revise_same_start' => 'Bu sanada boshlanadigan versiya allaqachon mavjud. Oʻrniga uni tahrirlang.',
        'version_overlap' => 'Bu davr fanning boshqa versiyasi bilan ustma-ust tushadi.',
        'requirement_set_overlap' => 'Bu oʻquv reja yillari dastur va oʻrin uchun boshqa talablar toʻplami bilan ustma-ust tushadi.',
    ],
    'import' => [
        'org_unit_required' => 'Yangi fanlar uchun org_unit (masʼul boʻlinma kodi) kerak.',
    ],
    'or' => 'yoki',
    'grading_bases' => [
        'graded' => 'Harfli baho',
        'pass_fail' => 'Oʻtdi/oʻtmadi',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Prerekvizit',
        'corequisite' => 'Korekvizit',
    ],
    'program_roles' => [
        'primary' => 'Asosiy dastur',
        'second_major' => 'Ikkinchi mutaxassislik',
        'minor' => 'Qoʻshimcha yoʻnalish',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Toifa boʻyicha kreditlar',
        'required_courses' => 'Majburiy fanlar',
        'course_group' => 'Fanlardan tanlash',
        'assessment_pass' => 'Topshirilgan imtihon',
    ],
];
