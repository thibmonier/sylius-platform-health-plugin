<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Summary;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthCategorySummary;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;

final class HealthSummaryBuilderTest extends TestCase
{
    public function testEachCategoryTakesTheWorstStatusOfItsChecks(): void
    {
        $summaries = (new HealthSummaryBuilder())->build($this->fiveChecksOverThreeCategories());

        self::assertSame(
            ['shop' => HealthStatus::Critical, 'data' => HealthStatus::Warning, 'environment' => HealthStatus::Ok],
            $this->statusByCategory($summaries),
        );
    }

    public function testTheCategoryWithTheCriticalCheckComesFirst(): void
    {
        $summaries = (new HealthSummaryBuilder())->build($this->fiveChecksOverThreeCategories());

        self::assertSame(HealthCategory::Shop, $summaries[0]->category);
    }

    public function testCategoriesOfTheSameSeverityKeepTheOrderOfTheEnumeration(): void
    {
        $summaries = (new HealthSummaryBuilder())->build([
            $this->report('shop.a', HealthCategory::Shop, HealthStatus::Warning),
            $this->report('data.a', HealthCategory::Data, HealthStatus::Warning),
        ]);

        self::assertSame(['data', 'shop'], array_keys($this->statusByCategory($summaries)));
    }

    public function testTheChecksOfACategoryAreListedMostSevereFirst(): void
    {
        $summaries = (new HealthSummaryBuilder())->build($this->fiveChecksOverThreeCategories());

        $data = array_values(array_filter($summaries, static fn (HealthCategorySummary $summary): bool => HealthCategory::Data === $summary->category))[0];

        self::assertSame(['data.warning', 'data.ok'], array_map(static fn (HealthCheckReport $report): string => $report->identifier, $data->reports));
    }

    public function testACategoryWhoseChecksAreAllUnknownIsNotCritical(): void
    {
        $summaries = (new HealthSummaryBuilder())->build([
            $this->report('plugins.a', HealthCategory::Plugins, HealthStatus::Unknown),
            $this->report('plugins.b', HealthCategory::Plugins, HealthStatus::Unknown),
        ]);

        self::assertSame(HealthStatus::Unknown, $summaries[0]->status);
    }

    public function testAnUnknownCheckKeepsAnOkCategoryFromBeingReportedOk(): void
    {
        $summaries = (new HealthSummaryBuilder())->build([
            $this->report('plugins.a', HealthCategory::Plugins, HealthStatus::Ok),
            $this->report('plugins.b', HealthCategory::Plugins, HealthStatus::Unknown),
        ]);

        self::assertSame(HealthStatus::Unknown, $summaries[0]->status);
    }

    public function testAnUnknownCheckNeverHidesAWarning(): void
    {
        $summaries = (new HealthSummaryBuilder())->build([
            $this->report('plugins.a', HealthCategory::Plugins, HealthStatus::Unknown),
            $this->report('plugins.b', HealthCategory::Plugins, HealthStatus::Warning),
        ]);

        self::assertSame(HealthStatus::Warning, $summaries[0]->status);
    }

    public function testWithoutAnyReportThereIsNoCategory(): void
    {
        self::assertSame([], (new HealthSummaryBuilder())->build([]));
    }

    /**
     * @return list<HealthCheckReport>
     */
    private function fiveChecksOverThreeCategories(): array
    {
        return [
            $this->report('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->report('data.ok', HealthCategory::Data, HealthStatus::Ok),
            $this->report('data.warning', HealthCategory::Data, HealthStatus::Warning),
            $this->report('shop.ok', HealthCategory::Shop, HealthStatus::Ok),
            $this->report('shop.critical', HealthCategory::Shop, HealthStatus::Critical),
        ];
    }

    /**
     * @param list<HealthCategorySummary> $summaries
     *
     * @return array<string, HealthStatus>
     */
    private function statusByCategory(array $summaries): array
    {
        $statuses = [];
        foreach ($summaries as $summary) {
            $statuses[$summary->category->value] = $summary->status;
        }

        return $statuses;
    }

    private function report(string $identifier, HealthCategory $category, HealthStatus $status): HealthCheckReport
    {
        return new HealthCheckReport($identifier, $category, new HealthResult($status, new TranslatableMessage($identifier)), 1.0);
    }
}
