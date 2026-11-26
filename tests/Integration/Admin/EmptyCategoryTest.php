<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Tmonier\SyliusPlatformHealthPlugin\Twig\Component\HealthDashboardComponent;
use Twig\Environment;

/**
 * A shop without the plugin that provides the probes of a category (US-303): the category is neither on the page nor in the
 * dashboard panel, never shown empty nor in error. Here the "plugins" category has no probe.
 */
final class EmptyCategoryTest extends KernelTestCase
{
    public function testACategoryWithoutProbeIsNotOnThePage(): void
    {
        $page = $this->page();

        self::assertCount(0, $page->filter('[data-test-health-category="plugins"]'));
        self::assertCount(2, $page->filter('[data-test-health-category]'));
    }

    public function testACategoryWithoutProbeIsNotInTheDashboardPanel(): void
    {
        $panel = $this->panel();

        self::assertCount(0, $panel->filter('[data-test-health-dashboard-category="plugins"]'));
        self::assertCount(2, $panel->filter('[data-test-health-dashboard-category]'));
    }

    private function page(): Crawler
    {
        $summaries = (new HealthSummaryBuilder())->build($this->provider()->snapshot()->reports);

        return new Crawler($this->twig()->render('@TmonierSyliusPlatformHealthPlugin/admin/_summary.html.twig', ['categories' => $summaries]));
    }

    private function panel(): Crawler
    {
        $component = new HealthDashboardComponent($this->provider(), new HealthSummaryBuilder(), new NullLogger());

        return new Crawler($this->twig()->render('@TmonierSyliusPlatformHealthPlugin/admin/dashboard.html.twig', [
            'this' => $component,
            'attributes' => '',
        ]));
    }

    private function provider(): HealthSnapshotProvider
    {
        $checks = [
            new FakeCheck('environment.demo', HealthCategory::Environment, static fn (): HealthResult => HealthResult::ok(new TranslatableMessage('Fine'))),
            new FakeCheck('data.demo', HealthCategory::Data, static fn (): HealthResult => HealthResult::warning(new TranslatableMessage('To watch'))),
        ];

        return new HealthSnapshotProvider(new HealthCheckRunner($checks, new NullLogger()), new ArrayAdapter(), new MockClock(), new NullLogger(), 60);
    }

    private function twig(): Environment
    {
        self::bootKernel();
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig;
    }
}
