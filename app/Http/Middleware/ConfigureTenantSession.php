<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConfigureTenantSession
{
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower((string) $request->getHost());
        $configuredDomain = $this->normalizeConfiguredDomain(config('session.domain'));

        config([
            'session.domain' => $this->resolveCookieDomain($host, $configuredDomain),
            'session.secure' => $this->resolveSecureSetting($request),
        ]);

        return $next($request);
    }

    private function resolveCookieDomain(string $host, ?string $configuredDomain): ?string
    {
        if ($host === '' || $this->shouldUseHostOnlyCookies($host)) {
            return null;
        }

        if ($configuredDomain === null) {
            return null;
        }

        if ($host === $configuredDomain || str_ends_with($host, '.' . $configuredDomain)) {
            return '.' . $configuredDomain;
        }

        return null;
    }

    private function resolveSecureSetting(Request $request): bool
    {
        $configured = config('session.secure');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return $request->isSecure();
    }

    private function shouldUseHostOnlyCookies(string $host): bool
    {
        return $host === 'localhost'
            || filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    private function normalizeConfiguredDomain(mixed $configuredDomain): ?string
    {
        $configuredDomain = strtolower(trim((string) $configuredDomain));

        if ($configuredDomain === '' || $configuredDomain === 'auto' || $configuredDomain === 'null') {
            return null;
        }

        return ltrim($configuredDomain, '.');
    }
}
