<?php

return [
    'mail' => [
        // 部署前替换为真实 SMTP 账号
        'protocol' => 'smtp',          // smtp（STARTTLS 587）；SSL 直连 465 时用 smtps
        'user' => 'username',
        'pass' => 'password',
        'host' => 'smtp.hostname.com',
        'port' => 587,
        'mail_addr' => 'user@hostname.com',
    ],
];
