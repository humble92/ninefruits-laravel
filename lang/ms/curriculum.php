<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'kursus',
            'plural_label' => 'kursus',
        ],
        'course_versions' => [
            'label' => 'versi kursus',
            'plural_label' => 'versi',
        ],
        'course_equivalencies' => [
            'label' => 'kesetaraan',
            'plural_label' => 'kursus setara',
        ],
        'requirement_sets' => [
            'label' => 'syarat bergraduat',
            'plural_label' => 'syarat bergraduat',
        ],
    ],
    'sections' => [
        'course' => 'Kursus',
        'first_version' => 'Versi pertama',
        'requirements' => 'Syarat',
    ],
    'fields' => [
        'assessment_type' => 'Jenis peperiksaan',
        'code' => 'Kod kursus',
        'managing_unit' => 'Unit pengurus',
        'title' => 'Nama kursus',
        'description' => 'Keterangan',
        'credits' => 'Kredit',
        'lecture_hours' => 'Jam kuliah',
        'lab_hours' => 'Jam amali',
        'category' => 'Kategori kursus',
        'grading_basis' => 'Penggredan',
        'counts_toward_credit_limit' => 'Dikira dalam had kredit',
        'is_repeatable' => 'Boleh diambil semula untuk kredit',
        'requisites' => 'Syarat kursus',
        'requisite_kind' => 'Jenis',
        'group_number' => 'Kumpulan',
        'required_course' => 'Kursus',
        'minimum_grade' => 'Gred minimum',
        'allows_concurrent' => 'Boleh diambil dalam semester yang sama',
        'effective_from' => 'Versi baharu mulai',
        'equivalent_course' => 'Kursus setara',
        'is_mutual' => 'Dua hala',
        'note' => 'Catatan',
        'program_role' => 'Peranan program',
        'catalog_year_from' => 'Tahun kurikulum dari',
        'catalog_year_to' => 'Tahun kurikulum hingga',
        'total_credits' => 'Jumlah kredit',
        'requirement_kind' => 'Syarat',
        'min_credits' => 'Kredit minimum',
        'min_courses' => 'Kursus minimum',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'Matikan bagi kursus di luar had kredit semester (cth. kursus bank kredit).',
        'is_repeatable' => 'Mengambilnya semula memberikan kredit sekali lagi (cth. seminar dengan topik berubah) dan bukannya dikira sebagai ulang kursus.',
        'requisites' => 'Kursus dalam kumpulan yang sama adalah alternatif (salah satu sudah memadai); setiap kumpulan mesti dipenuhi.',
        'revise' => 'Versi semasa tamat sehari sebelumnya; versi baharu bermula sebagai salinan versi tersebut beserta syarat kursusnya. Seksyen yang telah ditawarkan mengekalkan versinya.',
        'is_mutual' => 'Setiap satu dikira bagi yang lain. Dimatikan: hanya kursus setara dikira bagi kursus ini.',
        'catalog_year_to' => 'Kosong: juga bagi tahun kurikulum berikutnya sehingga set lain bermula.',
    ],
    'actions' => [
        'revise' => 'Versi baharu mulai tarikh',
        'add_requirement' => 'Tambah syarat',
    ],
    'notifications' => [
        'revised' => 'Versi baharu telah dicipta.',
    ],
    'errors' => [
        'no_version_to_revise' => 'Tiada versi yang berkuat kuasa pada tarikh tersebut.',
        'revise_same_start' => 'Satu versi sudah bermula pada tarikh tersebut. Sunting versi itu sebagai ganti.',
        'version_overlap' => 'Tempoh ini bertindih dengan versi lain kursus ini.',
        'requirement_set_overlap' => 'Tahun kurikulum ini bertindih dengan set syarat lain bagi program dan peranan tersebut.',
    ],
    'import' => [
        'org_unit_required' => 'Kursus baharu memerlukan org_unit (kod unit pengurus).',
    ],
    'or' => 'atau',
    'grading_bases' => [
        'graded' => 'Gred huruf',
        'pass_fail' => 'Lulus/gagal',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Prasyarat',
        'corequisite' => 'Kosyarat',
    ],
    'program_roles' => [
        'primary' => 'Program utama',
        'second_major' => 'Major kedua',
        'minor' => 'Minor',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Kredit dalam kategori',
        'required_courses' => 'Kursus wajib',
        'course_group' => 'Pilihan daripada kursus',
        'assessment_pass' => 'Lulus peperiksaan',
    ],
];
