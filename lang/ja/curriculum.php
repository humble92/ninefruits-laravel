<?php

return [
    'resources' => [
        'courses' => [
            'label' => '科目',
            'plural_label' => '科目',
        ],
        'course_versions' => [
            'label' => '科目バージョン',
            'plural_label' => 'バージョン',
        ],
        'course_equivalencies' => [
            'label' => '代替科目',
            'plural_label' => '代替科目',
        ],
        'requirement_sets' => [
            'label' => '卒業要件',
            'plural_label' => '卒業要件',
        ],
    ],
    'sections' => [
        'course' => '科目',
        'first_version' => '初版',
        'requirements' => '要件',
    ],
    'fields' => [
        'assessment_type' => '試験種別',
        'code' => '科目コード',
        'managing_unit' => '管理組織',
        'title' => '科目名',
        'description' => '説明',
        'credits' => '単位',
        'lecture_hours' => '講義時間',
        'lab_hours' => '実習時間',
        'category' => '科目区分',
        'grading_basis' => '評価方法',
        'counts_toward_credit_limit' => '履修単位上限に含める',
        'is_repeatable' => '繰り返し単位取得可',
        'requisites' => '履修条件',
        'requisite_kind' => '種類',
        'group_number' => 'グループ',
        'required_course' => '科目',
        'minimum_grade' => '最低成績',
        'allows_concurrent' => '同一学期の履修可',
        'effective_from' => '新バージョンの適用開始日',
        'equivalent_course' => '代替科目',
        'is_mutual' => '相互代替',
        'note' => '備考',
        'program_role' => 'プログラム区分',
        'catalog_year_from' => '適用教育課程年度(開始)',
        'catalog_year_to' => '適用教育課程年度(終了)',
        'total_credits' => '総単位数',
        'requirement_kind' => '要件',
        'min_credits' => '最低単位数',
        'min_courses' => '最低科目数',
    ],
    'help' => [
        'counts_toward_credit_limit' => '学期の履修単位上限の対象外とする科目(単位互換科目など)ではオフにします。',
        'is_repeatable' => '再履修ではなく、履修するたびに再度単位を取得できます(テーマが変わるゼミなど)。',
        'requisites' => '同じグループの科目はいずれか 1 つで足ります。すべてのグループを満たす必要があります。',
        'revise' => '現行バージョンは前日で終了し、新バージョンはその内容と履修条件をコピーして始まります。すでに開講済みの開講科目は元のバージョンのままです。',
        'is_mutual' => '互いに代替として扱います。オフの場合は、代替科目がこの科目の代わりとしてのみ認められます。',
        'catalog_year_to' => '空欄: 別の要件セットが始まるまで、以降の教育課程年度にも適用します。',
    ],
    'actions' => [
        'revise' => '日付を指定して改訂',
        'add_requirement' => '要件を追加',
    ],
    'notifications' => [
        'revised' => '新しいバージョンを作成しました。',
    ],
    'errors' => [
        'no_version_to_revise' => 'その日付に有効なバージョンがありません。',
        'revise_same_start' => 'その日付に始まるバージョンがすでにあります。そのバージョンを編集してください。',
        'version_overlap' => 'この期間は科目の別バージョンと重なっています。',
        'requirement_set_overlap' => 'この教育課程年度は、同じプログラム・区分の別の要件セットと重なっています。',
    ],
    'import' => [
        'org_unit_required' => '新しい科目には org_unit(管理組織のコード)が必要です。',
    ],
    'or' => 'または',
    'grading_bases' => [
        'graded' => '段階評価',
        'pass_fail' => '合否',
    ],
    'requisite_kinds' => [
        'prerequisite' => '先修科目',
        'corequisite' => '同時履修科目',
    ],
    'program_roles' => [
        'primary' => '主専攻',
        'second_major' => '第二専攻',
        'minor' => '副専攻',
    ],
    'requirement_kinds' => [
        'category_credits' => '区分別単位',
        'required_courses' => '必修科目',
        'course_group' => '選択科目群',
        'assessment_pass' => '試験合格',
    ],
];
