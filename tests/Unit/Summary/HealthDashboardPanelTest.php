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
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthDashboardPanel;

final class HealthDashboardPanelTest extends TestCase
{
    public function testItCountsTheCriticalCategories(): void
    {
        $panel = new HealthDashboardPanel([
            $this->summary(HealthCategory::Shop, HealthStatus::Critical),
            $this->summary(HealthCategory::Data, HealthStatus::Critical),
            $this->summary(HealthCategory::Environment, HealthStatus::Warning),
        ]);

        self::assertSame(2, $panel->criticalCount());
    }

    public function testAWarningAnUnknownOrAnOkCategoryIsNotCounted(): void
    {
        $panel = new HealthDashboardPanel([
            $this->summary(HealthCategory::Shop, HealthStatus::Warning),
            $this->summary(HealthCategory::Data, HealthStatus::Unknown),
            $this->summary(HealthCategory::Environment, HealthStatus::Ok),
        ]);

        self::assertSame(0, $panel->criticalCount());
    }

    public function testAPanelWithoutCategoryHasNoCriticalCategory(): void
    {
        self::assertSame(0, (new HealthDashboardPanel([]))->criticalCount());
    }

    private function summary(HealthCategory $category, HealthStatus $status): HealthCategorySummary
    {
        $result = match ($status) {
            HealthStatus::Ok => HealthResult::ok(new TranslatableMessage('tests.status')),
            HealthStatus::Warning => HealthResult::warning(new TranslatableMessage('tests.status')),
            HealthStatus::Critical => HealthResult::critical(new TranslatableMessage('tests.status')),
            HealthStatus::Unknown => HealthResult::unknown(new TranslatableMessage('tests.status')),
        };

        return new HealthCategorySummary($category, $status, [new HealthCheckReport($category->value . '.demo', $category, $result, 1)]);
    }
}
