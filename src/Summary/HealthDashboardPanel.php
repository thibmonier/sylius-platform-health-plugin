<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Summary;

/**
 * What the dashboard panel shows: one light per category (the same summaries as the page) and the number of critical categories.
 */
final readonly class HealthDashboardPanel
{
    /**
     * @param list<HealthCategorySummary> $categories
     */
    public function __construct(
        public array $categories,
    ) {
    }

    public function criticalCount(): int
    {
        return 0;
    }
}
