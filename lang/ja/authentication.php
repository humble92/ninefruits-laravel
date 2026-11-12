<?php

return [
    'logout' => 'ログアウト',
    'fields' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'new_password' => '新しいパスワード',
        'password_confirmation' => 'パスワード(確認)',
        'email_or_student_number' => 'メールアドレスまたは学籍番号',
    ],
    'login' => [
        'title' => 'ログイン',
        'remember_me' => 'ログインしたままにする',
        'forgot_password' => 'パスワードをお忘れですか?',
        'submit' => 'ログイン',
        'sso' => ':provider でログイン',
    ],
    'two_factor' => [
        'title' => '二要素認証',
        'instructions' => '認証アプリに表示された 6 桁のコードを入力してください。',
        'code' => 'コード',
        'use_recovery_code' => 'リカバリーコードを使用する',
        'recovery_code' => 'リカバリーコード',
        'remember_device' => 'このデバイスを記憶する',
        'submit' => '確認',
        'email_instructions' => 'メールでお送りした 6 桁のコードを入力してください。',
        'email_code' => 'メールのコード',
        'send_email_code' => '代わりにメールでコードを受け取る',
        'resend_email_code' => '新しいコードを送信',
    ],
    'forgot_password' => [
        'title' => 'パスワードの再設定',
        'instructions' => 'メールアドレスを入力してください。新しいパスワードを設定するためのリンクをお送りします。',
        'submit' => '再設定リンクを送信',
    ],
    'reset_password' => [
        'title' => '新しいパスワードの設定',
        'submit' => 'パスワードを再設定',
    ],
    'confirm_password' => [
        'title' => 'パスワードの確認',
        'instructions' => 'セキュリティのため、続行するにはパスワードを入力してください。',
        'submit' => '確認',
    ],
];
