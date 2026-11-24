<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Summary;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

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
        return \count(array_filter($this->categories, static fn (HealthCategorySummary $summary): bool => HealthStatus::Critical === $summary->status));
    }
}
