<?php
return [
    'to' => 'shayaa100@gmail.com',

    'smtp_host' => 'mail.jumaah.net',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',
    'smtp_user' => 'info@bluewaysa.com',
    'smtp_pass' => 'CHANGE_ME',
    'from_email' => 'info@bluewaysa.com',
    'from_name' => 'Blue Way | بلو وي',

    'turnstile_secret' => '',

    'admin_users' => [
        'info@bluewaysa.com' => 'PASTE_HASH_FROM_npm_run_admin-user',
    ],
    'session_secret' => 'PASTE_FROM_npm_run_admin-user',
    'github_token' => 'github_pat_...',
    'github_repo' => 'OWNER/REPO',
];
