<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\ProbeExecutions;

/**
 * The panel of the administration dashboard (US-289), on the Sylius test application and its five probes: it is a deferred
 * Live Component that reads the cache shared with the page, so the dashboard never waits for a probe.
 */
final class DashboardPanelTest extends WebTestCase
{
    private const DASHBOARD = '/admin/';

    private const PAGE = '/admin/platform-health';

    private const PANEL = '[data-live-name-value="tmonier_platform_health:dashboard"]';

    private const FIVE_PROBES = 5;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $cache = self::getContainer()->get('cache.app');
        self::assertInstanceOf(AdapterInterface::class, $cache);
        $cache->clear();
        ProbeExecutions::reset();
        $this->client->loginUser($this->admin(), 'admin');
    }

    public function testTheDashboardIsRenderedWithoutRunningAnyProbe(): void
    {
        $crawler = $this->client->request('GET', self::DASHBOARD);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter(self::PANEL));
        self::assertCount(0, $crawler->filter('[data-test-health-dashboard-category]'));
        self::assertSame(0, ProbeExecutions::count());
    }

    public function testThePanelCompletesWithOneLightPerCategoryWritingItsStatus(): void
    {
        $panel = $this->deferredPanel($this->client->request('GET', self::DASHBOARD));

        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());
        self::assertSame(
            ['shop:Critical', 'environment:Warning', 'data:Warning', 'scheduled_tasks:Not measurable', 'plugins:Not measurable'],
            $panel->filter('[data-test-health-dashboard-category]')->each(static fn (Crawler $row): string => $row->attr('data-test-health-dashboard-category') . ':' . trim($row->filter('[data-test-health-category-status]')->text())),
        );
    }

    public function testEachLightHasAnIconOfItsOwnBesideTheWrittenStatus(): void
    {
        $panel = $this->deferredPanel($this->client->request('GET', self::DASHBOARD));

        self::assertSame(
            ['critical', 'warning', 'warning', 'unknown', 'unknown'],
            $panel->filter('[data-test-health-dashboard-category] [data-test-health-icon]')->each(static fn (Crawler $icon): string => (string) $icon->attr('data-test-health-icon')),
        );
    }

    public function testALinkOpensThePageOfTheDetail(): void
    {
        $panel = $this->deferredPanel($this->client->request('GET', self::DASHBOARD));

        self::assertCount(1, $panel->filter('[data-test-health-dashboard-link]'));
        self::assertSame(self::PAGE, $panel->filter('[data-test-health-dashboard-link]')->attr('href'));
        self::assertStringContainsString('See the details', $panel->filter('[data-test-health-dashboard-link]')->text());
    }

    public function testTheTitleSaysHowManyCategoriesAreCritical(): void
    {
        $panel = $this->deferredPanel($this->client->request('GET', self::DASHBOARD));

        self::assertSame('1 critical category', trim($panel->filter('[data-test-health-dashboard-title]')->text()));
    }

    public function testTheLightsAreTheOnesOfThePage(): void
    {
        $dashboard = $this->deferredPanel($this->client->request('GET', self::DASHBOARD));
        $page = $this->client->request('GET', self::PAGE);
        $placeholder = $page->filter('[data-live-url-value]');
        $results = $this->client->request('POST', (string) $placeholder->attr('data-live-url-value'), ['data' => $this->body($placeholder)]);

        self::assertSame(
            $results->filter('[data-test-health-category]')->each(static fn (Crawler $category): string => $category->attr('data-test-health-category') . ':' . $category->attr('data-test-health-status')),
            $dashboard->filter('[data-test-health-dashboard-category]')->each(static fn (Crawler $row): string => $row->attr('data-test-health-dashboard-category') . ':' . $row->attr('data-test-health-status')),
        );
        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());
    }

    public function testAWarmCacheOfThePageRunsNoProbeForTheDashboard(): void
    {
        $page = $this->client->request('GET', self::PAGE);
        $placeholder = $page->filter('[data-live-url-value]');
        $this->client->request('POST', (string) $placeholder->attr('data-live-url-value'), ['data' => $this->body($placeholder)]);
        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());

        $panel = $this->deferredPanel($this->client->request('GET', self::DASHBOARD));

        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());
        self::assertCount(5, $panel->filter('[data-test-health-dashboard-category]'));
    }

    public function testTheDashboardFeedsTheCacheThePageReads(): void
    {
        $this->deferredPanel($this->client->request('GET', self::DASHBOARD));
        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());

        $page = $this->client->request('GET', self::PAGE);
        $placeholder = $page->filter('[data-live-url-value]');
        $this->client->request('POST', (string) $placeholder->attr('data-live-url-value'), ['data' => $this->body($placeholder)]);

        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());
    }

    private function deferredPanel(Crawler $dashboard): Crawler
    {
        $placeholder = $dashboard->filter(self::PANEL);
        self::assertCount(1, $placeholder);

        return $this->client->request('POST', (string) $placeholder->attr('data-live-url-value'), ['data' => $this->body($placeholder)]);
    }

    private function body(Crawler $placeholder): string
    {
        $props = json_decode((string) $placeholder->attr('data-live-props-value'), true, flags: \JSON_THROW_ON_ERROR);

        return json_encode(['props' => $props], \JSON_THROW_ON_ERROR);
    }

    private function admin(): AdminUserInterface
    {
        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $admin = $registry->getRepository(AdminUserInterface::class)->findOneBy(['username' => 'sylius']);
        self::assertInstanceOf(AdminUserInterface::class, $admin);

        return $admin;
    }
}
