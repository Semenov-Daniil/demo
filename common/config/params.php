<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'encryptionKey' => getenv('ENCRYPTION_KEY') ?: 'default_encryption_key',
    'superExpert' => [
        'login' => getenv('SUPER_EXPERT_LOGIN') ?: 'expert',
        'password' => getenv('SUPER_EXPERT_PASSWORD') ?: 'expert',
    ],
    'siteName' => 'demo.ru',
    'siteUser' => getenv('SITE_USER') ?: 'www-data',
    'siteGroup' => getenv('SITE_GROUP') ?: 'www-data',
    'systemPassword' => getenv('SYSTEM_PASSWD') ?: '',
];
