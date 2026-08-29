<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebRateLimitTest extends TestCase
{
    public function test_returns_429_when_web_rate_limit_is_exceeded(): void
    {
        config()->set('security.rate_limits.web', 2);

        $this->get(route('home'))->assertOk();
        $this->get(route('home'))->assertOk();

        $response = $this->get(route('home'));

        $response
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}
