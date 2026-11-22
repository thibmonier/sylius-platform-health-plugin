<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Summary;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * One category of the page: the worst status of its probes and its probes, the most severe first.
 */
final readonly class HealthCategorySummary
{
    /**
     * @param list<HealthCheckReport> $reports
     */
    public function __construct(
        public HealthCategory $category,
        public HealthStatus $status,
        public array $reports,
    ) {
    }
}
