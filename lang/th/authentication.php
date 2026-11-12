<?php

return [
    'logout' => 'ออกจากระบบ',
    'fields' => [
        'email' => 'อีเมล',
        'password' => 'รหัสผ่าน',
        'new_password' => 'รหัสผ่านใหม่',
        'password_confirmation' => 'ยืนยันรหัสผ่าน',
        'email_or_student_number' => 'อีเมลหรือรหัสนักศึกษา',
    ],
    'login' => [
        'title' => 'เข้าสู่ระบบ',
        'remember_me' => 'จดจำการเข้าสู่ระบบ',
        'forgot_password' => 'ลืมรหัสผ่าน?',
        'submit' => 'เข้าสู่ระบบ',
        'sso' => 'เข้าสู่ระบบด้วย :provider',
    ],
    'two_factor' => [
        'title' => 'การยืนยันตัวตนแบบสองขั้นตอน',
        'instructions' => 'กรอกรหัส 6 หลักจากแอปยืนยันตัวตนของท่าน',
        'code' => 'รหัส',
        'use_recovery_code' => 'ใช้รหัสกู้คืนแทน',
        'recovery_code' => 'รหัสกู้คืน',
        'remember_device' => 'จดจำอุปกรณ์นี้',
        'submit' => 'ยืนยัน',
        'email_instructions' => 'กรอกรหัส 6 หลักที่ระบบส่งไปยังอีเมลของท่าน',
        'email_code' => 'รหัสทางอีเมล',
        'send_email_code' => 'ส่งรหัสทางอีเมลแทน',
        'resend_email_code' => 'ส่งรหัสใหม่',
    ],
    'forgot_password' => [
        'title' => 'รีเซ็ตรหัสผ่าน',
        'instructions' => 'กรอกที่อยู่อีเมลของท่าน แล้วระบบจะส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ให้',
        'submit' => 'ส่งลิงก์รีเซ็ต',
    ],
    'reset_password' => [
        'title' => 'ตั้งรหัสผ่านใหม่',
        'submit' => 'รีเซ็ตรหัสผ่าน',
    ],
    'confirm_password' => [
        'title' => 'ยืนยันรหัสผ่าน',
        'instructions' => 'เพื่อความปลอดภัย กรุณายืนยันรหัสผ่านเพื่อดำเนินการต่อ',
        'submit' => 'ยืนยัน',
    ],
];
