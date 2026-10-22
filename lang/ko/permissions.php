<?php

return [
    'resources' => [
        'admin_panel' => '관리 화면',
        'users' => '사용자',
        'roles' => '역할',
        'role_assignments' => '역할 할당',
        'audit_records' => '감사 기록',
        'security_events' => '보안 이벤트',
        'security_settings' => '보안 설정',
        'queue_monitor' => '큐 모니터 (Horizon)',
        'institution_settings' => '기관 설정',
    ],
    'admin_panel' => ['access' => '관리 화면 사용'],
    'users' => [
        'view' => '사용자 조회',
        'create' => '사용자 생성',
        'update' => '사용자 수정',
        'reset_mfa' => '2단계 인증 초기화',
    ],
    'roles' => [
        'view' => '역할 조회',
        'manage' => '역할 생성 및 수정',
    ],
    'role_assignments' => ['manage' => '역할 할당 및 종료'],
    'audit_records' => ['view' => '감사 기록 조회'],
    'security_events' => ['view' => '보안 이벤트 조회'],
    'security_settings' => ['manage' => '보안 설정 관리'],
    'institution_settings' => ['manage' => '기관 설정 관리'],
    'queue_monitor' => ['view' => '큐 모니터 조회'],
];
