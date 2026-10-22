<?php

return [
    'navigation' => [
        'access' => '사용자와 권한',
        'audit' => '감사',
        'settings' => '설정',
        'portal' => '포털',
    ],
    'users' => [
        'label' => '사용자',
        'plural_label' => '사용자',
        'fields' => [
            'name' => '이름',
            'email' => '이메일',
            'locale' => '언어',
            'is_active' => '활성',
            'mfa_enrolled' => '2단계 인증',
            'last_login_at' => '최근 로그인',
        ],
        'actions' => [
            'reset_mfa' => '2단계 인증 초기화',
        ],
        'notifications' => [
            'mfa_reset' => '2단계 인증을 초기화했습니다.',
        ],
    ],
    'roles' => [
        'label' => '역할',
        'plural_label' => '역할',
        'sections' => [
            'general' => '기본 정보',
            'permissions' => '권한',
        ],
        'fields' => [
            'name' => '내부 이름',
            'name_help' => '영문 소문자, 숫자, 밑줄만 사용합니다. 예: registrar',
            'display_name' => '표시 이름',
            'description' => '설명',
            'persona' => '신분',
            'is_system' => '시스템 역할',
            'permissions_count' => '권한 수',
            'active_assignments_count' => '현재 할당 수',
        ],
        'system_role_permissions' => '시스템 역할은 항상 모든 권한을 가집니다.',
    ],
    'role_assignments' => [
        'title' => '역할 할당',
        'fields' => [
            'role' => '역할',
            'scope' => '범위',
            'scope_id' => '범위 대상 ID',
            'starts_at' => '시작',
            'ends_at' => '종료',
            'active' => '유효',
            'assigned_by' => '할당한 사람',
        ],
        'actions' => [
            'assign' => '역할 할당',
            'revoke' => '할당 종료',
        ],
        'notifications' => [
            'assigned' => '역할을 할당했습니다.',
            'revoked' => '할당을 종료했습니다.',
        ],
    ],
    'audit' => [
        'fields' => [
            'occurred_at' => '시각',
            'log' => '로그',
            'event' => '이벤트',
            'subject' => '대상',
            'actor' => '사용자',
            'properties' => '상세',
            'request_id' => '요청 ID',
            'ip_address' => 'IP 주소',
        ],
    ],
    'activities' => [
        'label' => '업무 감사',
        'plural_label' => '업무 감사 기록',
    ],
    'record_changes' => [
        'label' => '데이터 변경',
        'plural_label' => '데이터 변경 기록',
        'fields' => [
            'table' => '테이블',
            'operation' => '작업',
            'record_id' => '레코드 ID',
            'changed_columns' => '변경 컬럼',
            'database_user' => 'DB 사용자',
            'transaction_id' => '트랜잭션',
            'old_values' => '변경 전',
            'new_values' => '변경 후',
        ],
    ],
    'security_events' => [
        'label' => '보안 이벤트',
        'plural_label' => '보안 이벤트',
        'fields' => [
            'type' => '이벤트',
            'identifier' => '로그인 이름',
        ],
    ],
    'settings' => [
        'security' => [
            'title' => '보안',
            'mfa_section' => '2단계 인증',
            'mfa_description' => '변경 사항은 모든 사용자에게 즉시 적용됩니다.',
            'mfa_enforcement' => '정책',
            'mfa_required_roles' => '2단계 인증이 필수인 역할',
            'mfa_grace_period_days' => '유예 기간(일)',
            'mfa_grace_period_help' => '필수 정책이 처음 적용된 뒤 설정 없이 사용할 수 있는 일수입니다. 0이면 즉시 적용됩니다.',
            'mfa_remember_device_days' => '기기 기억 기간(일)',
            'mfa_remember_device_help' => '신뢰한 기기에서는 이 기간 동안 코드 입력을 생략합니다. 0이면 사용하지 않습니다.',
            'mfa_email_code_enabled' => '이메일 코드 허용',
            'mfa_email_code_help' => '사용자가 인증 앱 대신 이메일 일회용 코드를 선택할 수 있습니다. 끄면 이메일 코드는 2단계 인증으로 인정되지 않습니다.',
        ],
        'institution' => [
            'title' => '기관',
            'general' => '기본 정보',
            'name' => '기관 이름',
            'timezone' => '시간대',
            'languages' => '언어',
            'languages_help' => '사용자에게 제공할 언어입니다. 번역 폴더가 있는 언어만 표시됩니다.',
            'supported_locales' => '제공 언어',
            'default_locale' => '기본 언어',
        ],
    ],
];
