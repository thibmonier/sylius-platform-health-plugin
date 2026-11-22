<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Summary;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * Stub: one category per report, every category "ok", no ordering.
 */
final class HealthSummaryBuilder
{
    /**
     * @param list<HealthCheckReport> $reports
     *
     * @return list<HealthCategorySummary>
     */
    public function build(array $reports): array
    {
        return array_map(static fn (HealthCheckReport $report): HealthCategorySummary => new HealthCategorySummary($report->category, HealthStatus::Ok, [$report]), $reports);
    }
}
