<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function test_responses_include_baseline_security_headers(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_html_responses_include_content_security_policy_when_enabled(): void
    {
        config()->set('security.content_security_policy.enabled', true);

        $response = $this->get(route('home'));

        $response->assertHeader(
            'Content-Security-Policy',
            config()->string('security.content_security_policy.value')
        );
    }

    public function test_secure_responses_include_hsts_when_enabled(): void
    {
        config()->set('security.hsts.enabled', true);

        $response = $this->get('https://localhost/');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
