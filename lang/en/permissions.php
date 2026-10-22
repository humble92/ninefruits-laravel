<?php

return [
    'resources' => [
        'admin_panel' => 'Administration panel',
        'users' => 'Users',
        'roles' => 'Roles',
        'role_assignments' => 'Role assignments',
        'audit_records' => 'Audit records',
        'security_events' => 'Security events',
        'security_settings' => 'Security settings',
        'queue_monitor' => 'Queue monitor (Horizon)',
        'institution_settings' => 'Institution settings',
    ],
    'admin_panel' => ['access' => 'Open the administration panel'],
    'users' => [
        'view' => 'View users',
        'create' => 'Create users',
        'update' => 'Edit users',
        'reset_mfa' => 'Reset two-factor authentication',
    ],
    'roles' => [
        'view' => 'View roles',
        'manage' => 'Create and edit roles',
    ],
    'role_assignments' => ['manage' => 'Assign and end roles'],
    'audit_records' => ['view' => 'View audit records'],
    'security_events' => ['view' => 'View security events'],
    'security_settings' => ['manage' => 'Manage security settings'],
    'institution_settings' => ['manage' => 'Manage institution settings'],
    'queue_monitor' => ['view' => 'View the queue monitor'],
];
