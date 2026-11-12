<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'ders',
            'plural_label' => 'dersler',
        ],
        'course_versions' => [
            'label' => 'ders sürümü',
            'plural_label' => 'sürümler',
        ],
        'course_equivalencies' => [
            'label' => 'eşdeğerlik',
            'plural_label' => 'eşdeğer dersler',
        ],
        'requirement_sets' => [
            'label' => 'mezuniyet koşulları',
            'plural_label' => 'mezuniyet koşulları',
        ],
    ],
    'sections' => [
        'course' => 'Ders',
        'first_version' => 'İlk sürüm',
        'requirements' => 'Koşullar',
    ],
    'fields' => [
        'assessment_type' => 'Sınav türü',
        'code' => 'Ders kodu',
        'managing_unit' => 'Sorumlu birim',
        'title' => 'Ders adı',
        'description' => 'Açıklama',
        'credits' => 'Kredi',
        'lecture_hours' => 'Teorik saat',
        'lab_hours' => 'Uygulama saati',
        'category' => 'Ders türü',
        'grading_basis' => 'Notlandırma',
        'counts_toward_credit_limit' => 'Kredi sınırına dahil',
        'is_repeatable' => 'Kredi için tekrar alınabilir',
        'requisites' => 'Ön koşullar',
        'requisite_kind' => 'Tür',
        'group_number' => 'Grup',
        'required_course' => 'Ders',
        'minimum_grade' => 'En düşük not',
        'allows_concurrent' => 'Aynı dönemde alınabilir',
        'effective_from' => 'Yeni sürümün başlangıcı',
        'equivalent_course' => 'Eşdeğer ders',
        'is_mutual' => 'Karşılıklı',
        'note' => 'Not',
        'program_role' => 'Program türü',
        'catalog_year_from' => 'Müfredat yılı (başlangıç)',
        'catalog_year_to' => 'Müfredat yılı (bitiş)',
        'total_credits' => 'Toplam kredi',
        'requirement_kind' => 'Koşul',
        'min_credits' => 'En az kredi',
        'min_courses' => 'En az ders',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'Dönem kredi sınırı dışındaki dersler için kapatın (ör. kredi bankası dersleri).',
        'is_repeatable' => 'Tekrar alındığında, ders tekrarı sayılmak yerine yeniden kredi kazandırır (ör. konusu değişen seminerler).',
        'requisites' => 'Aynı gruptaki dersler birbirinin alternatifidir (biri yeterlidir); her grup karşılanmalıdır.',
        'revise' => 'Mevcut sürüm bir gün önce sona erer; yeni sürüm, onun ve ön koşullarının bir kopyasıyla başlar. Önceden açılmış şubeler kendi sürümlerini korur.',
        'is_mutual' => 'Her biri diğerinin yerine sayılır. Kapalı: yalnızca eşdeğer ders bu dersin yerine sayılır.',
        'catalog_year_to' => 'Boş: başka bir koşul seti başlayana kadar sonraki müfredat yılları için de geçerlidir.',
    ],
    'actions' => [
        'revise' => 'Bir tarihten itibaren revize et',
        'add_requirement' => 'Koşul ekle',
    ],
    'notifications' => [
        'revised' => 'Yeni sürüm oluşturuldu.',
    ],
    'errors' => [
        'no_version_to_revise' => 'O tarihte geçerli bir sürüm yok.',
        'revise_same_start' => 'O tarihte başlayan bir sürüm zaten var. Bunun yerine onu düzenleyin.',
        'version_overlap' => 'Bu süre dersin başka bir sürümüyle çakışıyor.',
        'requirement_set_overlap' => 'Bu müfredat yılları, programın ve türün başka bir koşul setiyle çakışıyor.',
    ],
    'import' => [
        'org_unit_required' => 'Yeni dersler için org_unit (sorumlu birimin kodu) gereklidir.',
    ],
    'or' => 'veya',
    'grading_bases' => [
        'graded' => 'Harf notu',
        'pass_fail' => 'Başarılı/başarısız',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Ön koşul',
        'corequisite' => 'Eş koşul',
    ],
    'program_roles' => [
        'primary' => 'Ana program',
        'second_major' => 'Çift ana dal',
        'minor' => 'Yan dal',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Bir ders türünde kredi',
        'required_courses' => 'Zorunlu dersler',
        'course_group' => 'Derslerden seçim',
        'assessment_pass' => 'Geçilmiş sınav',
    ],
];
