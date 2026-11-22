<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

/**
 * Where the administrator fixes the problem: a route name and its parameters, never a hard-coded URL.
 */
final readonly class HealthActionLink
{
    /**
     * @param array<string, scalar> $parameters
     */
    public function __construct(
        public string $route,
        public array $parameters = [],
    ) {
    }
}
