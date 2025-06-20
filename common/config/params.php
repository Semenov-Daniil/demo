<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'encryptionKey' => isset($_ENV['ENCRYPTION_KEY']) ? $_ENV['ENCRYPTION_KEY'] : (getenv('ENCRYPTION_KEY') ?: 'default_encryption_key'),
    'superExpert' => [
        'login' => isset($_ENV['SUPER_EXPERT_LOGIN']) ? $_ENV['SUPER_EXPERT_LOGIN'] : (getenv('SUPER_EXPERT_LOGIN') ?: 'expert'),
        'password' => isset($_ENV['SUPER_EXPERT_PASSWORD']) ? $_ENV['SUPER_EXPERT_PASSWORD'] : (getenv('SUPER_EXPERT_PASSWORD') ?: 'expert'),
    ],
    'siteName' => 'demo.ru',
    'siteUser' => isset($_ENV['SITE_USER']) ? $_ENV['SITE_USER'] : (getenv('SITE_USER') ?: 'www-data'),
    'siteGroup' => isset($_ENV['SITE_GROUP']) ? $_ENV['SITE_GROUP'] : (getenv('SITE_GROUP') ?: 'www-data'),
    'vhSuffix' => isset($_ENV['VH_SUFFIX']) ? $_ENV['VH_SUFFIX'] : (getenv('VH_SUFFIX') ?: '.demo.ru'),
];
