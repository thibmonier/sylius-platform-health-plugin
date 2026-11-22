<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Summary;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * Groups the reports by category. A category takes the worst status of its probes, in the order
 * critical, warning, unknown, ok: an "unknown" never counts as "critical", but it is never reported "ok" either.
 * The most severe category comes first, then the categories keep the order of the enumeration.
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
        $byCategory = [];
        foreach ($reports as $report) {
            $byCategory[$report->category->value][] = $report;
        }

        $summaries = [];
        foreach ($byCategory as $categoryReports) {
            $summaries[] = $this->summarize($categoryReports);
        }

        usort($summaries, fn (HealthCategorySummary $a, HealthCategorySummary $b): int => [$this->severity($b->status), $this->position($a)] <=> [$this->severity($a->status), $this->position($b)]);

        return $summaries;
    }

    /**
     * @param non-empty-list<HealthCheckReport> $reports
     */
    private function summarize(array $reports): HealthCategorySummary
    {
        usort($reports, fn (HealthCheckReport $a, HealthCheckReport $b): int => [$this->severity($b->result->status), $a->identifier] <=> [$this->severity($a->result->status), $b->identifier]);

        return new HealthCategorySummary($reports[0]->category, $reports[0]->result->status, $reports);
    }

    private function position(HealthCategorySummary $summary): int
    {
        return (int) array_search($summary->category, HealthCategory::cases(), true);
    }

    private function severity(HealthStatus $status): int
    {
        return match ($status) {
            HealthStatus::Critical => 3,
            HealthStatus::Warning => 2,
            HealthStatus::Unknown => 1,
            HealthStatus::Ok => 0,
        };
    }
}
