<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Http;

use Tmonier\SyliusPlatformHealthPlugin\Console\Verdict;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshot;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * What the public entry point says about a snapshot. Never a summary, a detail, a link or a message: the summaries
 * carry versions, paths and table names, which an anonymous reader of a monitoring URL must not learn (OWASP A01, A05).
 */
final readonly class PublicReport
{
    public function hasCriticalCheck(HealthSnapshot $snapshot): bool
    {
        return (new Verdict($snapshot->reports))->count(HealthStatus::Critical) > 0;
    }

    /**
     * @return array{status: string, generated_at: string}
     */
    public function global(HealthSnapshot $snapshot): array
    {
        return [
            'status' => (new Verdict($snapshot->reports))->status()->value,
            'generated_at' => $snapshot->generatedAt->format(\DATE_ATOM),
        ];
    }

    /**
     * @return array{status: string, generated_at: string, checks: list<array{identifier: string, category: string, status: string}>}
     */
    public function detail(HealthSnapshot $snapshot): array
    {
        return $this->global($snapshot) + [
            'checks' => array_map(
                static fn (HealthCheckReport $report): array => [
                    'identifier' => $report->identifier,
                    'category' => $report->category->value,
                    'status' => $report->result->status->value,
                ],
                $snapshot->reports,
            ),
        ];
    }
}
