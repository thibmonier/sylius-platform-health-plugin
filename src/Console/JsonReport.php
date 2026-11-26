<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Console;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Security\SafeText;

/**
 * The machine-readable report: the overall status, the moment of the measure and one entry per check, with no detail and no link.
 * Statuses and categories are the raw values (stable for a script); only the summary is text.
 */
final readonly class JsonReport
{
    public function __construct(
        private SafeText $safeText,
    ) {
    }

    /**
     * @param list<HealthCheckReport> $reports
     */
    public function render(array $reports, \DateTimeImmutable $generatedAt): string
    {
        return json_encode([
            'status' => (new Verdict($reports))->status()->value,
            'generated_at' => $generatedAt->format(\DATE_ATOM),
            'checks' => array_map($this->check(...), $reports),
        ], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * @return array{identifier: string, category: string, status: string, summary: string}
     */
    private function check(HealthCheckReport $report): array
    {
        return [
            'identifier' => $report->identifier,
            'category' => $report->category->value,
            'status' => $report->result->status->value,
            'summary' => $this->safeText->render($report->result->summary, $report->identifier),
        ];
    }
}
