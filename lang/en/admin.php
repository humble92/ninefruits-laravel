<?php

return [
    'navigation' => [
        'access' => 'Users and access',
        'audit' => 'Audit',
        'settings' => 'Settings',
        'portal' => 'Portal',
    ],
    'users' => [
        'label' => 'user',
        'plural_label' => 'users',
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'locale' => 'Language',
            'is_active' => 'Active',
            'mfa_enrolled' => 'Two-factor',
            'last_login_at' => 'Last sign-in',
        ],
        'actions' => [
            'reset_mfa' => 'Reset two-factor authentication',
        ],
        'notifications' => [
            'mfa_reset' => 'Two-factor authentication was reset.',
        ],
    ],
    'roles' => [
        'label' => 'role',
        'plural_label' => 'roles',
        'sections' => [
            'general' => 'General',
            'permissions' => 'Permissions',
        ],
        'fields' => [
            'name' => 'Internal name',
            'name_help' => 'Lowercase letters, digits and underscores, e.g. registrar.',
            'display_name' => 'Display name',
            'description' => 'Description',
            'persona' => 'Persona',
            'is_system' => 'System role',
            'permissions_count' => 'Permissions',
            'active_assignments_count' => 'Active assignments',
        ],
        'system_role_permissions' => 'System roles always have every permission.',
    ],
    'role_assignments' => [
        'title' => 'Role assignments',
        'fields' => [
            'role' => 'Role',
            'scope' => 'Scope',
            'scope_id' => 'Scope target ID',
            'starts_at' => 'Starts',
            'ends_at' => 'Ends',
            'active' => 'Active',
            'assigned_by' => 'Assigned by',
        ],
        'actions' => [
            'assign' => 'Assign role',
            'revoke' => 'End assignment',
        ],
        'notifications' => [
            'assigned' => 'Role assigned.',
            'revoked' => 'Assignment ended.',
        ],
    ],
    'audit' => [
        'fields' => [
            'occurred_at' => 'Time',
            'log' => 'Log',
            'event' => 'Event',
            'subject' => 'Record',
            'actor' => 'User',
            'properties' => 'Details',
            'request_id' => 'Request ID',
            'ip_address' => 'IP address',
        ],
    ],
    'activities' => [
        'label' => 'activity',
        'plural_label' => 'activity log',
    ],
    'record_changes' => [
        'label' => 'data change',
        'plural_label' => 'data changes',
        'fields' => [
            'table' => 'Table',
            'operation' => 'Operation',
            'record_id' => 'Record ID',
            'changed_columns' => 'Changed columns',
            'database_user' => 'Database user',
            'transaction_id' => 'Transaction',
            'old_values' => 'Before',
            'new_values' => 'After',
        ],
    ],
    'security_events' => [
        'label' => 'security event',
        'plural_label' => 'security events',
        'fields' => [
            'type' => 'Event',
            'identifier' => 'Sign-in name',
        ],
    ],
    'settings' => [
        'security' => [
            'title' => 'Security',
            'mfa_section' => 'Two-factor authentication',
            'mfa_description' => 'Changes apply to every user immediately.',
            'mfa_enforcement' => 'Policy',
            'mfa_required_roles' => 'Roles that must use two-factor authentication',
            'mfa_grace_period_days' => 'Grace period (days)',
            'mfa_grace_period_help' => 'Days a user can keep working after the requirement first applies to them. 0 means immediately.',
            'mfa_remember_device_days' => 'Remember devices for (days)',
            'mfa_remember_device_help' => 'Users can skip the code on a trusted device for this many days. 0 turns this off.',
            'mfa_email_code_enabled' => 'Allow email codes',
            'mfa_email_code_help' => 'Users can choose one-time codes by email instead of an authenticator app. Turning this off stops email codes from counting as two-factor authentication.',
        ],
        'institution' => [
            'title' => 'Institution',
            'general' => 'General',
            'name' => 'Institution name',
            'timezone' => 'Time zone',
            'languages' => 'Languages',
            'languages_help' => 'Languages offered to users. A language appears here when its translation folder exists.',
            'supported_locales' => 'Offered languages',
            'default_locale' => 'Default language',
        ],
    ],
];
