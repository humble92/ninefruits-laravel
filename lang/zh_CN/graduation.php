<?php

return [
    'resources' => [
        'audits' => ['label' => '毕业资格审核', 'plural_label' => '毕业资格审核'],
    ],
    'fields' => [
        'is_satisfied' => '满足',
        'earned_credits' => '已获学分',
        'audited_at' => '审核时间',
        'min_year_level' => '年级以上',
        'waived' => '豁免',
        'courses' => '门课程',
        'missing' => '未通过',
    ],
    'satisfied' => ['yes' => '满足', 'no' => '不满足'],
    'issues' => [
        'no_requirement_set' => '未定义培养方案年份 :year 的毕业要求。',
        'requirement' => '未满足的要求：:name',
        'total_credits' => '总学分 :earned，需要 :required。',
        'gpa' => '累计平均绩点 :gpa 低于最低要求 :minimum。',
    ],
    'errors' => [
        'not_enrolled' => '只有在读学生可以办理毕业。',
        'no_audit' => '没有已保存的毕业资格审核，请先执行审核。',
        'audit_not_satisfied' => '最新的毕业资格审核不满足。',
    ],
    'actions' => [
        'audit_now' => '毕业资格审核',
        'save_audit' => '保存此次审核',
        'run_audits' => '执行审核',
        'open' => '打开',
        'rerun' => '重新审核',
        'waive' => '豁免要求',
        'graduate' => '办理毕业',
    ],
    'notifications' => [
        'audit_saved' => '已保存毕业资格审核。',
        'audits_run' => '已审核 :students 名学生，满足 :satisfied 名。',
        'waived' => '已豁免该要求。',
        'graduated' => '已办理毕业 :count 名，未办理 :errors 名。',
    ],
    'portal' => [
        'title' => '毕业要求',
        'intro' => '按培养方案年份 :year 的要求评估的当前完成情况。豁免请咨询所在院系。',
    ],
    'reports' => [
        'title' => '报表',
        'report' => '报表',
        'rows' => ':count 行（屏幕显示前 500 行，CSV 包含全部）',
        'download_csv' => '下载 CSV',
        'empty' => '没有数据。请在上方选择条件。',
        'names' => [
            'students' => '学生名单',
            'headcount' => '按院系、专业、年级、状态的人数',
            'term_records' => '学期成绩汇总（学业警告、优秀）',
            'graduation_candidates' => '预计毕业生（最新审核）',
            'status_changes' => '学籍异动',
            'section_enrollments' => '各教学班选课人数',
            'not_registered' => '学期未选课学生',
        ],
    ],
    'report_columns' => [
        'student_number' => '学号', 'student_name' => '姓名', 'program_name' => '专业', 'program_code' => '专业代码', 'program_role' => '修读类别',
        'org_unit_name' => '院系', 'org_unit_code' => '院系代码', 'year_level' => '年级', 'status' => '状态', 'admission_date' => '入学日期', 'graduation_date' => '毕业日期',
        'degree_number' => '学位编号', 'students' => '人数', 'term_code' => '学期', 'attempted_credits' => '选课学分', 'earned_credits' => '已获学分', 'term_gpa' => '学期绩点',
        'cumulative_earned_credits' => '累计已获', 'cumulative_gpa' => '累计绩点', 'academic_standing' => '学业状态', 'warning_count' => '警告次数', 'is_honors' => '优秀',
        'honors_rank' => '优秀名次', 'is_satisfied' => '满足', 'required_credits' => '所需学分', 'audited_at' => '审核时间', 'type_name' => '异动', 'from_status' => '异动前',
        'to_status' => '异动后', 'effective_date' => '生效日期', 'decided_at' => '决定时间', 'course_code' => '课程', 'course_title' => '课程名称', 'section_code' => '教学班',
        'instructors' => '任课教师', 'capacity' => '容量', 'enrolled' => '已选', 'waiting' => '候补',
    ],
];
