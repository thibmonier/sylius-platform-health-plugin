<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class TableGrowthCheck implements HealthCheckInterface
{
    /**
     * @param array<string, int> $thresholds rows from which each declared table warns (critical beyond the double)
     */
    public function __construct(
        private TableRowEstimatorInterface $estimator,
        private array $thresholds,
    ) {
    }

    public function identifier(): string
    {
        return 'data.table_growth';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        if ([] === $this->thresholds) {
            return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.table_growth.none'));
        }

        $estimates = $this->estimator->estimate(array_keys($this->thresholds));
        if (null === $estimates) {
            return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.table_growth.unsupported_platform'));
        }

        return $this->worst($estimates) ?? HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.table_growth.ok', ['%count%' => \count($this->thresholds)]));
    }

    /**
     * The table furthest beyond its threshold, the others beyond theirs counted in the detail; then a missing table.
     *
     * @param array<string, int> $estimates
     */
    private function worst(array $estimates): ?HealthResult
    {
        $over = [];
        foreach ($this->thresholds as $table => $threshold) {
            if (isset($estimates[$table]) && $estimates[$table] > $threshold) {
                $over[$table] = $estimates[$table] / $threshold;
            }
        }
        if ([] !== $over) {
            arsort($over);

            return $this->over(array_key_first($over), $estimates, \count($over) - 1);
        }

        foreach (array_keys($this->thresholds) as $table) {
            if (!isset($estimates[$table])) {
                return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.table_growth.missing', ['%table%' => $table]), null, DocumentationLink::create());
            }
        }

        return null;
    }

    /**
     * @param array<string, int> $estimates
     */
    private function over(string $table, array $estimates, int $others): HealthResult
    {
        $threshold = $this->thresholds[$table];
        $summary = new TranslatableMessage('tmonier_platform_health.data.table_growth.over', ['%table%' => $table, '%rows%' => $estimates[$table], '%threshold%' => $threshold]);
        $detail = $others > 0 ? new TranslatableMessage('tmonier_platform_health.data.table_growth.others', ['%count%' => $others]) : null;

        return $estimates[$table] > 2 * $threshold
            ? HealthResult::critical($summary, $detail, DocumentationLink::create())
            : HealthResult::warning($summary, $detail, DocumentationLink::create());
    }
}
