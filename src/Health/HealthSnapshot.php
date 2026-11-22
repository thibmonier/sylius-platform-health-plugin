<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

/**
 * The reports of one run of every probe, and the moment they were measured: what the cache keeps and what the page shows.
 */
final readonly class HealthSnapshot
{
    /**
     * @param list<HealthCheckReport> $reports
     */
    public function __construct(
        public array $reports,
        public \DateTimeImmutable $generatedAt,
    ) {
    }
}
