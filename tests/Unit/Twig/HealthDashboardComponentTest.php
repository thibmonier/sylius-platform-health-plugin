<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\FailingClock;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Tmonier\SyliusPlatformHealthPlugin\Twig\Component\HealthDashboardComponent;

final class HealthDashboardComponentTest extends TestCase
{
    private int $runs = 0;

    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->runs = 0;
        $this->logger = new RecordingLogger();
    }

    protected function tearDown(): void
    {
        FailingClock::recover();
    }

    public function testBuildingTheComponentRunsNoProbe(): void
    {
        $component = $this->component($this->provider(new ArrayAdapter(), new MockClock()));

        self::assertSame(0, $this->runs);
        self::assertInstanceOf(HealthDashboardComponent::class, $component);
    }

    public function testThePanelHoldsOneSummaryPerCategory(): void
    {
        $panel = $this->component($this->provider(new ArrayAdapter(), new MockClock()))->getPanel();

        self::assertNotNull($panel);
        self::assertSame([HealthCategory::Shop, HealthCategory::Data], array_map(static fn (\Tmonier\SyliusPlatformHealthPlugin\Summary\HealthCategorySummary $summary): HealthCategory => $summary->category, $panel->categories));
        self::assertSame(1, $panel->criticalCount());
    }

    public function testTheResultsAreReadFromTheCacheTheOtherComponentsShare(): void
    {
        $cache = new ArrayAdapter();
        $clock = new MockClock();

        $this->component($this->provider($cache, $clock))->getPanel();
        $this->component($this->provider($cache, $clock))->getPanel();

        // Two probes, run once each: the second component found the results of the first in the cache.
        self::assertSame(2, $this->runs);
    }

    public function testAProviderThatThrowsGivesNoPanelAndTheExceptionIsLoggedWithItsTrace(): void
    {
        FailingClock::failFromNowOn();

        $panel = $this->component($this->provider(new ArrayAdapter(), new FailingClock()))->getPanel();

        self::assertNull($panel);
        $logged = $this->logger->loggedExceptions();
        self::assertCount(1, $logged);
        self::assertSame(FailingClock::MESSAGE, $logged[0]->getMessage());
    }

    private function component(HealthSnapshotProvider $provider): HealthDashboardComponent
    {
        return new HealthDashboardComponent($provider, new HealthSummaryBuilder(), $this->logger);
    }

    private function provider(ArrayAdapter $cache, ClockInterface $clock): HealthSnapshotProvider
    {
        $count = function (): void {
            ++$this->runs;
        };
        $checks = [
            new FakeCheck('shop.demo', HealthCategory::Shop, function () use ($count): HealthResult {
                $count();

                return HealthResult::critical(new TranslatableMessage('tests.critical'));
            }),
            new FakeCheck('data.demo', HealthCategory::Data, function () use ($count): HealthResult {
                $count();

                return HealthResult::ok(new TranslatableMessage('tests.ok'));
            }),
        ];

        return new HealthSnapshotProvider(new HealthCheckRunner($checks, new NullLogger()), $cache, $clock, new NullLogger(), 60);
    }
}
