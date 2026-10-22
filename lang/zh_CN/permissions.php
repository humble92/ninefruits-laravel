<?php

return [
    'resources' => [
        'admin_panel' => '管理界面',
        'users' => '用户',
        'roles' => '角色',
        'role_assignments' => '角色分配',
        'audit_records' => '审计记录',
        'security_events' => '安全事件',
        'security_settings' => '安全设置',
        'queue_monitor' => '队列监控 (Horizon)',
        'institution_settings' => '机构设置',
    ],
    'admin_panel' => ['access' => '使用管理界面'],
    'users' => [
        'view' => '查看用户',
        'create' => '创建用户',
        'update' => '编辑用户',
        'reset_mfa' => '重置双重身份验证',
    ],
    'roles' => [
        'view' => '查看角色',
        'manage' => '创建和编辑角色',
    ],
    'role_assignments' => ['manage' => '分配和结束角色'],
    'audit_records' => ['view' => '查看审计记录'],
    'security_events' => ['view' => '查看安全事件'],
    'security_settings' => ['manage' => '管理安全设置'],
    'institution_settings' => ['manage' => '管理机构设置'],
    'queue_monitor' => ['view' => '查看队列监控'],
];
