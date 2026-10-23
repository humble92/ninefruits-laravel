<?php

return [
    'resources' => [
        'audits' => ['label' => '졸업 요건 점검', 'plural_label' => '졸업 요건 점검'],
    ],
    'fields' => [
        'is_satisfied' => '충족',
        'earned_credits' => '취득 학점',
        'audited_at' => '점검 시각',
        'min_year_level' => '학년 이상',
        'waived' => '면제',
        'courses' => '과목',
        'missing' => '미이수',
    ],
    'satisfied' => ['yes' => '충족', 'no' => '미충족'],
    'issues' => [
        'no_requirement_set' => '교육과정 연도 :year의 졸업 요건이 정의되지 않았습니다.',
        'requirement' => '미충족 요건: :name',
        'total_credits' => '총 취득 학점 :earned / 필요 :required.',
        'gpa' => '누계 평점 :gpa이 최저 기준 :minimum에 미달합니다.',
    ],
    'errors' => [
        'not_enrolled' => '재학 중인 학생만 졸업 처리할 수 있습니다.',
        'no_audit' => '저장된 졸업 요건 점검이 없습니다. 먼저 점검을 실행하세요.',
        'audit_not_satisfied' => '최신 졸업 요건 점검이 미충족입니다.',
    ],
    'actions' => [
        'audit_now' => '졸업 요건 점검',
        'save_audit' => '이 점검 저장',
        'run_audits' => '점검 실행',
        'open' => '열기',
        'rerun' => '다시 점검',
        'waive' => '요건 면제',
        'graduate' => '졸업 처리',
    ],
    'notifications' => [
        'audit_saved' => '졸업 요건 점검을 저장했습니다.',
        'audits_run' => '학생 :students명 점검, 충족 :satisfied명.',
        'waived' => '요건을 면제했습니다.',
        'graduated' => '졸업 처리 :count명, 미처리 :errors명.',
    ],
    'portal' => [
        'title' => '졸업 요건',
        'intro' => '교육과정 연도 :year 요건에 대한 현재 이수 상황입니다. 면제는 학과에 문의하세요.',
    ],
    'reports' => [
        'title' => '보고서',
        'report' => '보고서',
        'rows' => ':count행(화면에는 500행까지, CSV에는 전부)',
        'download_csv' => 'CSV 내려받기',
        'empty' => '행이 없습니다. 위에서 조건을 고르세요.',
        'names' => [
            'students' => '학생 명단',
            'headcount' => '조직·과정·학년·상태별 인원',
            'term_records' => '학기 성적 요약(학사경고, 성적우수)',
            'graduation_candidates' => '졸업 예정자(최신 점검)',
            'status_changes' => '학적 변동',
            'section_enrollments' => '강좌별 수강 인원',
            'not_registered' => '학기 미수강 학생',
        ],
    ],
    'report_columns' => [
        'student_number' => '학번', 'student_name' => '성명', 'program_name' => '과정', 'program_code' => '과정 코드', 'program_role' => '이수 구분',
        'org_unit_name' => '조직', 'org_unit_code' => '조직 코드', 'year_level' => '학년', 'status' => '상태', 'admission_date' => '입학일', 'graduation_date' => '졸업일',
        'degree_number' => '학위번호', 'students' => '인원', 'term_code' => '학기', 'attempted_credits' => '신청 학점', 'earned_credits' => '취득 학점', 'term_gpa' => '학기 평점',
        'cumulative_earned_credits' => '누계 취득', 'cumulative_gpa' => '누계 평점', 'academic_standing' => '학사 상태', 'warning_count' => '경고 횟수', 'is_honors' => '성적우수',
        'honors_rank' => '우수 순위', 'is_satisfied' => '충족', 'required_credits' => '필요 학점', 'audited_at' => '점검 시각', 'type_name' => '변동', 'from_status' => '변동 전',
        'to_status' => '변동 후', 'effective_date' => '효력일', 'decided_at' => '결정 시각', 'course_code' => '과목', 'course_title' => '과목명', 'section_code' => '분반',
        'instructors' => '담당 교원', 'capacity' => '정원', 'enrolled' => '수강', 'waiting' => '대기',
    ],
];
