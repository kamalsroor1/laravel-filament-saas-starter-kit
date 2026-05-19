<?php

declare(strict_types=1);

return [
    'saas_enabled' => filter_var(env('SAAS_MODE', true), FILTER_VALIDATE_BOOL),

    'modules' => [
        'tenancy' => true,
        'billing' => true,
        'notifications' => true,
        'api' => true,
        'webhooks' => true,
        'ai' => false,
        'audit_logs' => true,
        'settings' => true,
        'media' => true,
    ],
];

