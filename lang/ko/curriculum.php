<?php

return [
    'resources' => [
        'courses' => [
            'label' => '교과목',
            'plural_label' => '교과목',
        ],
        'course_versions' => [
            'label' => '교과목 버전',
            'plural_label' => '버전',
        ],
        'course_equivalencies' => [
            'label' => '대체과목',
            'plural_label' => '대체과목',
        ],
        'requirement_sets' => [
            'label' => '졸업 요건',
            'plural_label' => '졸업 요건',
        ],
    ],
    'sections' => [
        'course' => '교과목',
        'first_version' => '첫 버전',
        'requirements' => '요건',
    ],
    'fields' => [
        'code' => '교과목 코드',
        'managing_unit' => '관리 조직',
        'title' => '교과목명',
        'description' => '교과목 개요',
        'credits' => '학점',
        'lecture_hours' => '이론 시간',
        'lab_hours' => '실습 시간',
        'category' => '교과 구분',
        'grading_basis' => '성적 평가',
        'counts_toward_credit_limit' => '학기 학점 상한에 포함',
        'is_repeatable' => '반복 이수 가능',
        'requisites' => '선수·동시수강 과목',
        'requisite_kind' => '구분',
        'group_number' => '묶음',
        'required_course' => '과목',
        'minimum_grade' => '최저 성적',
        'allows_concurrent' => '같은 학기 수강 허용',
        'effective_from' => '새 버전 시작일',
        'equivalent_course' => '대체과목',
        'is_mutual' => '상호 인정',
        'note' => '비고',
        'program_role' => '이수 구분',
        'catalog_year_from' => '적용 교육과정 연도(부터)',
        'catalog_year_to' => '적용 교육과정 연도(까지)',
        'total_credits' => '졸업 학점',
        'requirement_kind' => '요건 종류',
        'min_credits' => '최소 학점',
        'min_courses' => '최소 과목 수',
    ],
    'help' => [
        'counts_toward_credit_limit' => '학기 학점 상한 밖의 과목(예: 학점은행 과목)은 끈다.',
        'is_repeatable' => '다시 들어도 재수강이 아니라 학점을 또 받는 과목(예: 주제가 바뀌는 세미나).',
        'requisites' => '같은 묶음의 과목은 하나만 이수하면 되고(또는), 묶음은 모두 충족해야 한다(그리고).',
        'revise' => '현재 버전은 전날 끝나고, 새 버전은 현재 버전과 선수과목을 복사해 시작한다. 이미 개설된 강좌는 원래 버전을 유지한다.',
        'is_mutual' => '서로 대신 인정한다. 끄면 대체과목이 이 과목을 대신하는 것만 인정한다.',
        'catalog_year_to' => '비우면 다른 요건이 시작될 때까지 이후 연도에도 적용한다.',
    ],
    'actions' => [
        'revise' => '날짜부터 개정',
        'add_requirement' => '요건 추가',
    ],
    'notifications' => [
        'revised' => '새 버전을 만들었습니다.',
    ],
    'errors' => [
        'no_version_to_revise' => '그 날짜에 유효한 버전이 없습니다.',
        'revise_same_start' => '그 날짜에 시작하는 버전이 이미 있습니다. 그 버전을 수정하세요.',
        'version_overlap' => '이 기간은 같은 교과목의 다른 버전과 겹칩니다.',
        'requirement_set_overlap' => '같은 프로그램, 이수 구분의 다른 요건과 적용 연도가 겹칩니다.',
    ],
    'import' => [
        'org_unit_required' => '새 교과목에는 org_unit(관리 조직 코드)이 필요합니다.',
    ],
    'or' => '또는',
    'grading_bases' => [
        'graded' => '등급제',
        'pass_fail' => 'P/F',
    ],
    'requisite_kinds' => [
        'prerequisite' => '선수과목',
        'corequisite' => '동시수강 과목',
    ],
    'program_roles' => [
        'primary' => '주전공',
        'second_major' => '복수전공',
        'minor' => '부전공',
    ],
    'requirement_kinds' => [
        'category_credits' => '교과 구분별 학점',
        'required_courses' => '필수 과목',
        'course_group' => '지정 과목 중 선택',
    ],
];
