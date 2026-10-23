<?php

return [
    'resources' => [
        'courses' => [
            'label' => '课程',
            'plural_label' => '课程',
        ],
        'course_versions' => [
            'label' => '课程版本',
            'plural_label' => '版本',
        ],
        'course_equivalencies' => [
            'label' => '替代课程',
            'plural_label' => '替代课程',
        ],
        'requirement_sets' => [
            'label' => '毕业要求',
            'plural_label' => '毕业要求',
        ],
    ],
    'sections' => [
        'course' => '课程',
        'first_version' => '初始版本',
        'requirements' => '要求',
    ],
    'fields' => [
        'assessment_type' => '考试种类',
        'code' => '课程代码',
        'managing_unit' => '管理单位',
        'title' => '课程名称',
        'description' => '课程简介',
        'credits' => '学分',
        'lecture_hours' => '理论学时',
        'lab_hours' => '实验学时',
        'category' => '课程类别',
        'grading_basis' => '成绩评定',
        'counts_toward_credit_limit' => '计入学期学分上限',
        'is_repeatable' => '可重复修读计学分',
        'requisites' => '先修与同修课程',
        'requisite_kind' => '类型',
        'group_number' => '组',
        'required_course' => '课程',
        'minimum_grade' => '最低成绩',
        'allows_concurrent' => '允许同学期修读',
        'effective_from' => '新版本开始日期',
        'equivalent_course' => '替代课程',
        'is_mutual' => '相互认可',
        'note' => '备注',
        'program_role' => '修读类型',
        'catalog_year_from' => '适用培养方案年份（起）',
        'catalog_year_to' => '适用培养方案年份（止）',
        'total_credits' => '毕业学分',
        'requirement_kind' => '要求类型',
        'min_credits' => '最低学分',
        'min_courses' => '最少门数',
    ],
    'help' => [
        'counts_toward_credit_limit' => '不计入学期学分上限的课程（如学分银行课程）请关闭。',
        'is_repeatable' => '再次修读可再次获得学分（如主题变化的研讨课），而非重修。',
        'requisites' => '同一组内课程任修其一即可（或），所有组都须满足（且）。',
        'revise' => '当前版本于前一天结束，新版本复制当前版本及其先修课程开始。已开设的课程保留原版本。',
        'is_mutual' => '相互替代。关闭时仅替代课程可代替本课程。',
        'catalog_year_to' => '留空表示在新的要求开始前对之后年份同样适用。',
    ],
    'actions' => [
        'revise' => '自某日修订',
        'add_requirement' => '添加要求',
    ],
    'notifications' => [
        'revised' => '已创建新版本。',
    ],
    'errors' => [
        'no_version_to_revise' => '该日期没有生效的版本。',
        'revise_same_start' => '已有从该日期开始的版本，请直接修改。',
        'version_overlap' => '该期间与该课程的其他版本重叠。',
        'requirement_set_overlap' => '适用年份与同一专业、同一修读类型的其他要求重叠。',
    ],
    'import' => [
        'org_unit_required' => '新课程需要 org_unit（管理单位代码）。',
    ],
    'or' => '或',
    'grading_bases' => [
        'graded' => '等级制',
        'pass_fail' => '通过/不通过',
    ],
    'requisite_kinds' => [
        'prerequisite' => '先修课程',
        'corequisite' => '同修课程',
    ],
    'program_roles' => [
        'primary' => '主修',
        'second_major' => '第二专业',
        'minor' => '辅修',
    ],
    'requirement_kinds' => [
        'category_credits' => '类别学分',
        'required_courses' => '必修课程',
        'course_group' => '指定课程中选修',
        'assessment_pass' => '考试合格',
    ],
];
