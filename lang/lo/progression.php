<?php

// Use English for progression labels that do not yet have a local translation.
return array_replace_recursive(require __DIR__.'/../en/progression.php', [
    'title' => 'ການເລື່ອນປີຮຽນ',
    'rule' => 'ກົດເກນການເລື່ອນປີຮຽນ',
    'rules' => 'ກົດເກນການເລື່ອນປີຮຽນ',
    'basis' => 'ເກນການຄິດໄລ່',
    'thresholds' => 'ຄ່າຂັ້ນຕ່ຳສຳລັບເລື່ອນປີຮຽນ',
    'default_rule' => 'ຄ່າເລີ່ມຕົ້ນຂອງສະຖາບັນ',
    'inherit' => 'ໃຊ້ຄ່າເລີ່ມຕົ້ນຂອງສະຖາບັນ',
    'preview' => 'ສ້າງລາຍການກວດກ່ອນ',
    'scheduled_at' => 'ເວລານຳໃຊ້',
    'schedule' => 'ອະນຸມັດ ແລະກຳນົດເວລາ',
    'student' => 'ລະຫັດນັກສຶກສາ',
    'program' => 'ຫຼັກສູດ',
    'change' => 'ການປ່ຽນລະດັບປີຮຽນ',
    'bases' => [
        'credits' => 'ໜ່ວຍກິດທີ່ໄດ້',
        'terms' => 'ພາກຮຽນທີ່ຮຽນຈົບ',
        'manual' => 'ຈັດການດ້ວຍຕົນເອງ',
    ],
    'statuses' => [
        'draft' => 'ຮ່າງ',
        'scheduled' => 'ຕາມກຳນົດ',
    ],
    'outcomes' => [
        'pending' => 'ລໍຖ້າພິຈາລະນາ',
    ],
]);
