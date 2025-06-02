<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'encryptionKey' => $_ENV['ENCRYPTION_KEY'] ?: 'default_encryption_key',
    'superExpert' => [
        'login' => $_ENV['SUPER_EXPERT_LOGIN'] ?: 'expert',
        'password' => $_ENV['SUPER_EXPERT_PASSWORD'] ?: 'expert',
    ],
    'siteName' => 'demo.ru',
    'siteUser' => $_ENV['SITE_USER'] ?: 'www-data',
    'siteGroup' => $_ENV['SITE_GROUP'] ?: 'www-data',
];
