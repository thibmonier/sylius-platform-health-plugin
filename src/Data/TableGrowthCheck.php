<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
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
        // Red stub (T-294-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        $this->estimator->estimate(array_keys($this->thresholds));

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
