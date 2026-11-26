<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Console;

use Symfony\Contracts\Translation\TranslatorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Security\SafeText;

/**
 * One line per check (status spelled out, identifier, summary) under the heading of its category, then a summary line.
 */
final readonly class TextReport
{
    public function __construct(
        private TranslatorInterface $translator,
        private SafeText $safeText,
    ) {
    }

    /**
     * @param list<HealthCheckReport> $reports
     *
     * @return list<string>
     */
    public function lines(array $reports): array
    {
        if ([] === $reports) {
            return [$this->translator->trans('tmonier_platform_health.console.empty')];
        }

        $lines = [];
        foreach (HealthCategory::cases() as $category) {
            $lines = [...$lines, ...$this->category($category, $reports)];
        }

        return [...$lines, $this->summary(new Verdict($reports))];
    }

    /**
     * @param list<HealthCheckReport> $reports
     *
     * @return list<string>
     */
    private function category(HealthCategory $category, array $reports): array
    {
        $own = array_values(array_filter($reports, static fn (HealthCheckReport $report): bool => $report->category === $category));
        if ([] === $own) {
            return [];
        }

        return [
            $this->translator->trans('tmonier_platform_health.category.' . $category->value),
            ...array_map($this->line(...), $own),
            '',
        ];
    }

    private function line(HealthCheckReport $report): string
    {
        $summary = $this->safeText->render($report->result->summary, $report->identifier);

        return sprintf('  [%s] %s : %s', $this->status($report->result->status), $report->identifier, (string) preg_replace('/\s+/', ' ', $summary));
    }

    private function summary(Verdict $verdict): string
    {
        return $this->translator->trans('tmonier_platform_health.console.summary', [
            '%status%' => $this->status($verdict->status()),
            '%total%' => $verdict->total(),
            '%critical%' => $verdict->count(HealthStatus::Critical),
            '%warning%' => $verdict->count(HealthStatus::Warning),
            '%unknown%' => $verdict->count(HealthStatus::Unknown),
            '%ok%' => $verdict->count(HealthStatus::Ok),
        ]);
    }

    private function status(HealthStatus $status): string
    {
        return $this->translator->trans('tmonier_platform_health.status.' . $status->value);
    }
}
