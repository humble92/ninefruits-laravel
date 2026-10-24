<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'ວິຊາ',
            'plural_label' => 'ວິຊາ',
        ],
        'course_versions' => [
            'label' => 'ສະບັບວິຊາ',
            'plural_label' => 'ສະບັບ',
        ],
        'course_equivalencies' => [
            'label' => 'ວິຊາທຽບເທົ່າ',
            'plural_label' => 'ວິຊາທຽບເທົ່າ',
        ],
        'requirement_sets' => [
            'label' => 'ເງື່ອນໄຂຈົບການສຶກສາ',
            'plural_label' => 'ເງື່ອນໄຂຈົບການສຶກສາ',
        ],
    ],
    'sections' => [
        'course' => 'ວິຊາ',
        'first_version' => 'ສະບັບທຳອິດ',
        'requirements' => 'ເງື່ອນໄຂ',
    ],
    'fields' => [
        'assessment_type' => 'ປະເພດການສອບເສັງ',
        'code' => 'ລະຫັດວິຊາ',
        'managing_unit' => 'ໜ່ວຍງານຮັບຜິດຊອບ',
        'title' => 'ຊື່ວິຊາ',
        'description' => 'ຄຳອະທິບາຍ',
        'credits' => 'ໜ່ວຍກິດ',
        'lecture_hours' => 'ຊົ່ວໂມງບັນຍາຍ',
        'lab_hours' => 'ຊົ່ວໂມງປະຕິບັດ',
        'category' => 'ປະເພດວິຊາ',
        'grading_basis' => 'ການໃຫ້ເກຣດ',
        'counts_toward_credit_limit' => 'ນັບເຂົ້າໃນຂີດຈຳກັດໜ່ວຍກິດ',
        'is_repeatable' => 'ສາມາດຮຽນຊ້ຳເພື່ອຮັບໜ່ວຍກິດອີກໄດ້',
        'requisites' => 'ວິຊາບັງຄັບກ່ອນ/ຄວບ',
        'requisite_kind' => 'ປະເພດ',
        'group_number' => 'ກຸ່ມ',
        'required_course' => 'ວິຊາ',
        'minimum_grade' => 'ເກຣດຕ່ຳສຸດ',
        'allows_concurrent' => 'ສາມາດຮຽນໃນພາກຮຽນດຽວກັນໄດ້',
        'effective_from' => 'ສະບັບໃໝ່ມີຜົນຕັ້ງແຕ່',
        'equivalent_course' => 'ວິຊາທຽບເທົ່າ',
        'is_mutual' => 'ທຽບເທົ່າກັນທັງສອງທາງ',
        'note' => 'ໝາຍເຫດ',
        'program_role' => 'ບົດບາດໃນຫຼັກສູດ',
        'catalog_year_from' => 'ປີຫຼັກສູດເລີ່ມ',
        'catalog_year_to' => 'ປີຫຼັກສູດສິ້ນສຸດ',
        'total_credits' => 'ໜ່ວຍກິດລວມ',
        'requirement_kind' => 'ເງື່ອນໄຂ',
        'min_credits' => 'ໜ່ວຍກິດຕ່ຳສຸດ',
        'min_courses' => 'ຈຳນວນວິຊາຕ່ຳສຸດ',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'ປິດສຳລັບວິຊາທີ່ຢູ່ນອກຂີດຈຳກັດໜ່ວຍກິດຕໍ່ພາກຮຽນ (ເຊັ່ນ ວິຊາທະນາຄານໜ່ວຍກິດ).',
        'is_repeatable' => 'ການຮຽນອີກຄັ້ງຈະໄດ້ຮັບໜ່ວຍກິດອີກ (ເຊັ່ນ ສຳມະນາທີ່ປ່ຽນຫົວຂໍ້) ແທນທີ່ຈະຖືວ່າເປັນການຮຽນຊ້ຳ.',
        'requisites' => 'ວິຊາໃນກຸ່ມດຽວກັນເປັນທາງເລືອກ (ຜ່ານໜຶ່ງວິຊາກໍພຽງພໍ); ທຸກກຸ່ມຕ້ອງຜ່ານເງື່ອນໄຂ.',
        'revise' => 'ສະບັບປັດຈຸບັນສິ້ນສຸດໃນມື້ກ່ອນໜ້າ; ສະບັບໃໝ່ເລີ່ມດ້ວຍສຳເນົາຂອງມັນ ແລະ ວິຊາບັງຄັບກ່ອນ/ຄວບຂອງມັນ. ກຸ່ມຮຽນທີ່ເປີດແລ້ວຍັງຄົງໃຊ້ສະບັບເດີມ.',
        'is_mutual' => 'ແຕ່ລະວິຊານັບແທນກັນໄດ້. ຖ້າປິດ: ມີແຕ່ວິຊາທຽບເທົ່າເທົ່ານັ້ນທີ່ນັບແທນວິຊານີ້ໄດ້.',
        'catalog_year_to' => 'ຫວ່າງ: ໃຊ້ກັບປີຫຼັກສູດຕໍ່ໆໄປຈົນກວ່າຈະມີຊຸດອື່ນເລີ່ມ.',
    ],
    'actions' => [
        'revise' => 'ປັບປຸງຕັ້ງແຕ່ວັນທີ',
        'add_requirement' => 'ເພີ່ມເງື່ອນໄຂ',
    ],
    'notifications' => [
        'revised' => 'ສ້າງສະບັບໃໝ່ແລ້ວ.',
    ],
    'errors' => [
        'no_version_to_revise' => 'ບໍ່ມີສະບັບທີ່ມີຜົນໃນວັນທີນັ້ນ.',
        'revise_same_start' => 'ມີສະບັບທີ່ເລີ່ມໃນວັນທີນັ້ນຢູ່ແລ້ວ. ກະລຸນາແກ້ໄຂສະບັບນັ້ນແທນ.',
        'version_overlap' => 'ຊ່ວງເວລານີ້ຊ້ອນກັບສະບັບອື່ນຂອງວິຊາ.',
        'requirement_set_overlap' => 'ປີຫຼັກສູດເຫຼົ່ານີ້ຊ້ອນກັບຊຸດເງື່ອນໄຂອື່ນຂອງຫຼັກສູດ ແລະ ບົດບາດດຽວກັນ.',
    ],
    'import' => [
        'org_unit_required' => 'ວິຊາໃໝ່ຕ້ອງມີ org_unit (ລະຫັດຂອງໜ່ວຍງານຮັບຜິດຊອບ).',
    ],
    'or' => 'ຫຼື',
    'grading_bases' => [
        'graded' => 'ເກຣດຕົວອັກສອນ',
        'pass_fail' => 'ຜ່ານ/ບໍ່ຜ່ານ',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'ວິຊາບັງຄັບກ່ອນ',
        'corequisite' => 'ວິຊາບັງຄັບຄວບ',
    ],
    'program_roles' => [
        'primary' => 'ຫຼັກສູດຫຼັກ',
        'second_major' => 'ວິຊາເອກທີສອງ',
        'minor' => 'ວິຊາໂທ',
    ],
    'requirement_kinds' => [
        'category_credits' => 'ໜ່ວຍກິດໃນປະເພດວິຊາ',
        'required_courses' => 'ວິຊາບັງຄັບ',
        'course_group' => 'ເລືອກຈາກກຸ່ມວິຊາ',
        'assessment_pass' => 'ຜ່ານການສອບເສັງ',
    ],
];
