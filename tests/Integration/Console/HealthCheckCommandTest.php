<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ReadsJsonOutput;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshot;

/**
 * The command in the test application, where the probes of the application, of the plugin and of the demonstration
 * plugin (demo_webhooks.outbox "ok", demo_webhooks.broken "unknown") are all registered.
 */
final class HealthCheckCommandTest extends KernelTestCase
{
    use ReadsJsonOutput;

    private const SNAPSHOT_KEY = 'tmonier_platform_health.snapshot';

    public function testTheCommandIsRegisteredAndAnOkProbeExitsWithZero(): void
    {
        $tester = $this->tester();

        self::assertSame(0, $tester->execute(['--only' => ['demo_webhooks.outbox']]));
        self::assertStringContainsString('demo_webhooks.outbox', $tester->getDisplay());
    }

    public function testAWarningProbeExitsWithOne(): void
    {
        self::assertSame(1, $this->tester()->execute(['--only' => ['data.demo']]));
    }

    public function testAnUnknownProbeExitsWithOne(): void
    {
        self::assertSame(1, $this->tester()->execute(['--only' => ['demo_webhooks.broken']]));
    }

    public function testACriticalProbeExitsWithTwo(): void
    {
        self::assertSame(2, $this->tester()->execute(['--only' => ['shop.demo']]));
    }

    public function testAnUnknownOnlyExitsWithThreeAndNamesTheValue(): void
    {
        $tester = $this->tester();

        self::assertSame(3, $tester->execute(['--only' => ['data.inconnue']]));
        self::assertStringContainsString('data.inconnue', $tester->getDisplay());
    }

    public function testTheWholeApplicationInJsonIsValidAndCriticalBecauseOfTheDemonstrationShopProbe(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['--format' => 'json']);

        self::assertSame(2, $exitCode);
        self::assertSame('critical', self::document($tester->getDisplay())['status'] ?? null);
        self::assertContains('demo_webhooks.outbox', self::identifiers($tester->getDisplay()));
        self::assertContains('demo_webhooks.broken', self::identifiers($tester->getDisplay()));
    }

    public function testAProbeThatThrowsIsUnknownAndItsMessageNeverReachesTheOutput(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['--only' => ['plugins.failing', 'demo_webhooks.broken']]);

        self::assertSame(1, $exitCode);
        self::assertStringNotContainsString('hunter2', $tester->getDisplay());
        self::assertStringNotContainsString('outbox table cannot be read', $tester->getDisplay());
        self::assertStringNotContainsString('RuntimeException', $tester->getDisplay());
    }

    public function testTheCommandNeitherReadsNorWritesTheSharedCacheOfThePage(): void
    {
        $cache = $this->cache();
        $cache->delete(self::SNAPSHOT_KEY);
        $poisoned = new HealthSnapshot([new HealthCheckReport('poison.cached', HealthCategory::Plugins, HealthResult::critical(new TranslatableMessage('Read from the cache')), 1.0)], new \DateTimeImmutable('2026-01-01'));
        $cache->get(self::SNAPSHOT_KEY, static function (ItemInterface $item) use ($poisoned): HealthSnapshot {
            $item->expiresAfter(300);

            return $poisoned;
        });
        $tester = $this->tester();

        $tester->execute(['--only' => ['demo_webhooks.outbox']]);

        self::assertStringContainsString('demo_webhooks.outbox', $tester->getDisplay());
        self::assertStringNotContainsString('poison.cached', $tester->getDisplay());
        self::assertSame(0, $tester->getStatusCode());
        $kept = $cache->get(self::SNAPSHOT_KEY, static fn (): HealthSnapshot => new HealthSnapshot([], new \DateTimeImmutable()));
        self::assertInstanceOf(HealthSnapshot::class, $kept);
        self::assertSame(['poison.cached'], array_map(static fn (HealthCheckReport $report): string => $report->identifier, $kept->reports));
        $cache->delete(self::SNAPSHOT_KEY);
    }

    private function tester(): CommandTester
    {
        $kernel = self::bootKernel();

        return new CommandTester((new Application($kernel))->find('tmonier:health:check'));
    }

    private function cache(): CacheInterface
    {
        $cache = self::getContainer()->get('cache.app');
        self::assertInstanceOf(CacheInterface::class, $cache);

        return $cache;
    }
}
