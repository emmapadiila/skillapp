<?php

return [
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), geolocation=(), microphone=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ],

    'content_security_policy' => [
        'enabled' => (bool) env(
            'SECURITY_CSP_ENABLED',
            env('APP_ENV', 'production') === 'production'
        ),
        'value' => implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "connect-src 'self'",
            "font-src 'self' data:",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "img-src 'self' data:",
            "object-src 'none'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            'upgrade-insecure-requests',
        ]),
    ],

    'hsts' => [
        'enabled' => (bool) env(
            'SECURITY_HSTS_ENABLED',
            env('APP_ENV', 'production') === 'production'
        ),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31_536_000),
    ],

    'rate_limits' => [
        'web' => (int) env('WEB_RATE_LIMIT_PER_MINUTE', 120),
        'api' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
        'auth' => (int) env('AUTH_RATE_LIMIT_PER_MINUTE', 10),
    ],
];
