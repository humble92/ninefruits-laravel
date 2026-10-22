<?php

return [
    'navigation' => [
        'reference_data' => '基础数据',
        'access' => '用户与权限',
        'audit' => '审计',
        'settings' => '设置',
        'portal' => '门户',
    ],
    'users' => [
        'label' => '用户',
        'plural_label' => '用户',
        'fields' => [
            'name' => '姓名',
            'email' => '电子邮箱',
            'locale' => '语言',
            'is_active' => '启用',
            'mfa_enrolled' => '双重验证',
            'last_login_at' => '最近登录',
        ],
        'actions' => [
            'reset_mfa' => '重置双重身份验证',
        ],
        'notifications' => [
            'mfa_reset' => '已重置双重身份验证。',
        ],
    ],
    'roles' => [
        'label' => '角色',
        'plural_label' => '角色',
        'sections' => [
            'general' => '基本信息',
            'permissions' => '权限',
        ],
        'fields' => [
            'name' => '内部名称',
            'name_help' => '仅使用小写英文字母、数字和下划线，例如 registrar。',
            'display_name' => '显示名称',
            'description' => '说明',
            'persona' => '身份',
            'is_system' => '系统角色',
            'permissions_count' => '权限数',
            'active_assignments_count' => '当前分配数',
        ],
        'system_role_permissions' => '系统角色始终拥有全部权限。',
    ],
    'role_assignments' => [
        'title' => '角色分配',
        'fields' => [
            'role' => '角色',
            'scope' => '范围',
            'scope_target' => '范围对象',
            'starts_at' => '开始',
            'ends_at' => '结束',
            'active' => '有效',
            'assigned_by' => '分配人',
        ],
        'actions' => [
            'assign' => '分配角色',
            'revoke' => '结束分配',
        ],
        'notifications' => [
            'assigned' => '已分配角色。',
            'revoked' => '已结束分配。',
        ],
    ],
    'audit' => [
        'fields' => [
            'occurred_at' => '时间',
            'log' => '日志',
            'event' => '事件',
            'subject' => '对象',
            'actor' => '用户',
            'properties' => '详情',
            'request_id' => '请求 ID',
            'ip_address' => 'IP 地址',
        ],
    ],
    'activities' => [
        'label' => '业务审计',
        'plural_label' => '业务审计记录',
    ],
    'record_changes' => [
        'label' => '数据变更',
        'plural_label' => '数据变更记录',
        'fields' => [
            'table' => '表',
            'operation' => '操作',
            'record_id' => '记录 ID',
            'changed_columns' => '变更字段',
            'database_user' => '数据库用户',
            'transaction_id' => '事务',
            'old_values' => '变更前',
            'new_values' => '变更后',
        ],
    ],
    'security_events' => [
        'label' => '安全事件',
        'plural_label' => '安全事件',
        'fields' => [
            'type' => '事件',
            'identifier' => '登录名',
        ],
    ],
    'settings' => [
        'security' => [
            'title' => '安全',
            'mfa_section' => '双重身份验证',
            'mfa_description' => '更改会立即对所有用户生效。',
            'mfa_enforcement' => '策略',
            'mfa_required_roles' => '必须使用双重验证的角色',
            'mfa_grace_period_days' => '宽限期（天）',
            'mfa_grace_period_help' => '要求首次适用于用户后，可继续使用而不设置的天数。0 表示立即生效。',
            'mfa_remember_device_days' => '记住设备（天）',
            'mfa_remember_device_help' => '在受信任的设备上，此期间内可免输验证码。0 表示不使用。',
            'mfa_email_code_enabled' => '允许邮件验证码',
            'mfa_email_code_help' => '用户可以选择通过邮件接收一次性验证码来代替身份验证器应用。关闭后，邮件验证码不再视为双重身份验证。',
        ],
        'institution' => [
            'title' => '机构',
            'general' => '基本信息',
            'name' => '机构名称',
            'timezone' => '时区',
            'languages' => '语言',
            'languages_help' => '向用户提供的语言。仅显示存在翻译文件夹的语言。',
            'supported_locales' => '提供的语言',
            'default_locale' => '默认语言',
        ],
    ],
];
