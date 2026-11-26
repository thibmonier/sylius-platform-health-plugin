<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\TableGrowthCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\TableRowEstimatorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class TableGrowthCheckTest extends TestCase
{
    use ResultAssertions;

    public function testWithoutAWatchedTableTheProbeSaysSoAndIsOk(): void
    {
        self::assertSummary($this->execute([], []), HealthStatus::Ok, 'tmonier_platform_health.data.table_growth.none');
    }

    public function testTablesUnderTheirThresholdAreOk(): void
    {
        $result = $this->execute(['tmonier_audit_log' => 100000, 'sylius_order' => 500000], ['tmonier_audit_log' => 40000, 'sylius_order' => 1200]);

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.data.table_growth.ok', ['%count%' => 2]);
    }

    public function testATableBeyondItsThresholdWarnsWithItsNameAndEstimate(): void
    {
        $result = $this->execute(['tmonier_audit_log' => 100000, 'sylius_order' => 500000], ['tmonier_audit_log' => 150000, 'sylius_order' => 1200]);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.table_growth.over', ['%table%' => 'tmonier_audit_log', '%rows%' => 150000, '%threshold%' => 100000]);
    }

    public function testATableBeyondTheDoubleOfItsThresholdIsCritical(): void
    {
        $result = $this->execute(['tmonier_audit_log' => 100000], ['tmonier_audit_log' => 200001]);

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.data.table_growth.over', ['%table%' => 'tmonier_audit_log', '%rows%' => 200001]);
    }

    public function testTheWorstTableIsTheOneShownAndTheOthersAreCounted(): void
    {
        $result = $this->execute(['a_log' => 100, 'b_log' => 100, 'c_log' => 100], ['a_log' => 150, 'b_log' => 900, 'c_log' => 10]);

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.data.table_growth.over', ['%table%' => 'b_log']);
        self::assertDetail($result, 'tmonier_platform_health.data.table_growth.others', ['%count%' => 1]);
    }

    public function testADeclaredTableThatDoesNotExistIsUnknown(): void
    {
        $result = $this->execute(['tmonier_audit_log' => 100000], []);

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.data.table_growth.missing', ['%table%' => 'tmonier_audit_log']);
    }

    public function testAPlatformWhoseCatalogueCannotBeReadIsUnknown(): void
    {
        $result = $this->execute(['tmonier_audit_log' => 100000], null);

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.data.table_growth.unsupported_platform');
    }

    /**
     * @param array<string, int>      $thresholds
     * @param array<string, int>|null $estimates
     */
    private function execute(array $thresholds, ?array $estimates): HealthResult
    {
        return (new TableGrowthCheck(new class($estimates) implements TableRowEstimatorInterface {
            /** @param array<string, int>|null $estimates */
            public function __construct(private readonly ?array $estimates)
            {
            }

            public function estimate(array $tables): ?array
            {
                return null === $this->estimates ? null : array_intersect_key($this->estimates, array_flip($tables));
            }
        }, $thresholds))->run();
    }
}
