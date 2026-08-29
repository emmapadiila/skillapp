<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(private Repository $config) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ((array) $this->config->get('security.headers', []) as $name => $value) {
            $response->headers->set((string) $name, (string) $value);
        }

        if ($this->shouldAddContentSecurityPolicy($response)) {
            $response->headers->set(
                'Content-Security-Policy',
                (string) $this->config->get('security.content_security_policy.value')
            );
        }

        if ($request->isSecure() && (bool) $this->config->get('security.hsts.enabled')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.(int) $this->config->get('security.hsts.max_age')
            );
        }

        return $response;
    }

    private function shouldAddContentSecurityPolicy(Response $response): bool
    {
        if (! (bool) $this->config->get('security.content_security_policy.enabled')) {
            return false;
        }

        return str_starts_with(
            (string) $response->headers->get('Content-Type'),
            'text/html'
        );
    }
}
