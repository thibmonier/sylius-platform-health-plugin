<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

/**
 * The outcome of one probe as the runner hands it over: who answered, in which category, what, and how long it took.
 */
final readonly class HealthCheckReport
{
    public function __construct(
        public string $identifier,
        public HealthCategory $category,
        public HealthResult $result,
        public float $durationMs,
    ) {
    }
}
