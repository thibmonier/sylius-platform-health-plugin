<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Health;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;

final class HealthSnapshotProviderTest extends TestCase
{
    private int $runs = 0;

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->runs = 0;
        $this->clock = new MockClock('2026-11-22 10:00:00');
    }

    public function testASecondCallWithinTheTtlReusesTheResultsAndRunsNoCheckAgain(): void
    {
        $provider = $this->provider(60);

        $first = $provider->snapshot();
        $this->clock->sleep(10);
        $second = $provider->snapshot();

        self::assertSame(1, $this->runs);
        self::assertEquals($first, $second);
    }

    public function testTheChecksRunAgainOnceTheTtlHasElapsed(): void
    {
        $provider = $this->provider(1);

        $provider->snapshot();
        // The cache of Symfony 6.4 reads the real time, it cannot be driven by a mock clock.
        usleep(1_200_000);
        $provider->snapshot();

        self::assertSame(2, $this->runs);
    }

    public function testAnExplicitRefreshRunsTheChecksAgainEvenWithinTheTtl(): void
    {
        $provider = $this->provider(60);

        $provider->snapshot();
        $this->clock->sleep(10);
        $provider->snapshot(refresh: true);

        self::assertSame(2, $this->runs);
    }

    public function testARefreshReplacesTheCachedResults(): void
    {
        $provider = $this->provider(60);

        $provider->snapshot();
        $this->clock->sleep(10);
        $refreshed = $provider->snapshot(refresh: true);
        $this->clock->sleep(10);
        $afterwards = $provider->snapshot();

        self::assertSame(2, $this->runs);
        self::assertEquals($refreshed, $afterwards);
    }

    public function testTheSnapshotCarriesTheMomentOfTheMeasure(): void
    {
        $snapshot = $this->provider(60)->snapshot();

        self::assertEquals(new \DateTimeImmutable('2026-11-22 10:00:00'), $snapshot->generatedAt);
    }

    public function testAZeroTtlDisablesTheSharing(): void
    {
        $provider = $this->provider(0);

        $provider->snapshot();
        $provider->snapshot();

        self::assertSame(2, $this->runs);
    }

    private function provider(int $ttl): HealthSnapshotProvider
    {
        $check = new FakeCheck('data.counting', HealthCategory::Data, function (): HealthResult {
            ++$this->runs;

            return HealthResult::ok(new TranslatableMessage('tests.counting'));
        });

        return new HealthSnapshotProvider(
            new HealthCheckRunner([$check], new NullLogger()),
            new ArrayAdapter(),
            $this->clock,
            new NullLogger(),
            $ttl,
        );
    }
}
