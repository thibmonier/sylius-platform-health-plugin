<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\CreatesAdministrators;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Twig\Environment;

/**
 * WCAG 2.2 AA of the page (US-291), read in the HTML: the status is written and drawn, never carried by a colour
 * (1.4.1), the detail is a table that a screen reader announces (1.3.1), the refresh is announced without moving the focus.
 */
final class AccessibleSummaryTest extends WebTestCase
{
    use CreatesAdministrators;
    use InteractsWithLiveComponents;

    private const LABELS = ['ok' => 'OK', 'warning' => 'Warning', 'critical' => 'Critical', 'unknown' => 'Not measurable'];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $cache = self::getContainer()->get('cache.app');
        self::assertInstanceOf(AdapterInterface::class, $cache);
        $cache->clear();
    }

    protected function tearDown(): void
    {
        $this->removeAdministrators();
        parent::tearDown();
    }

    public function testEveryStatusIsWrittenNextToItsCategoryAndItsCheck(): void
    {
        $crawler = $this->renderFourStatuses();

        foreach (self::LABELS as $status => $label) {
            $category = $crawler->filter(sprintf('[data-test-health-category="%s"] [data-test-health-category-status]', $this->categoryOf($status)));
            $check = $crawler->filter(sprintf('[data-test-health-check="%s.a"] [data-test-health-status-label]', $this->categoryOf($status)));
            self::assertCount(1, $category, $status . ' category');
            self::assertCount(1, $check, $status . ' check');
            self::assertStringContainsString($label, $category->text(), $status . ' category');
            self::assertStringContainsString($label, $check->text(), $status . ' check');
        }
    }

    public function testTwoStatusesNeverShareTheirWordNorTheirIcon(): void
    {
        $crawler = $this->renderFourStatuses();

        $icons = $crawler->filter('[data-test-health-check] svg[data-test-health-icon]')->each(static fn (Crawler $icon): string => $icon->attr('data-test-health-icon') . '|' . $icon->html());
        $words = $crawler->filter('[data-test-health-check] [data-test-health-status-label]')->each(static fn (Crawler $label): string => $label->text());

        self::assertCount(4, array_unique($icons));
        self::assertCount(4, array_unique($words));
    }

    public function testEveryIconIsDecorativeForAssistiveTechnology(): void
    {
        $crawler = $this->renderFourStatuses();

        $icons = $crawler->filter('svg[data-test-health-icon]');
        self::assertCount(8, $icons);
        self::assertSame(['true'], array_unique($icons->each(static fn (Crawler $icon): ?string => $icon->attr('aria-hidden'))));
        self::assertSame(['false'], array_unique($icons->each(static fn (Crawler $icon): ?string => $icon->attr('focusable'))));
    }

    public function testTheDetailOfACategoryIsATableWithACaptionAndHeaders(): void
    {
        $crawler = $this->renderFourStatuses();

        $table = $crawler->filter('[data-test-health-category="shop"] table');
        self::assertCount(1, $table);
        self::assertCount(1, $table->filter('caption'));
        self::assertStringContainsString('Shop', $table->filter('caption')->text());

        $columns = $table->filter('thead th');
        self::assertCount(4, $columns);
        self::assertSame(['col'], array_unique($columns->each(static fn (Crawler $column): ?string => $column->attr('scope'))));
    }

    public function testEachCheckHasARowHeaderAndTheTableIsInsideTheNativeDetails(): void
    {
        $crawler = $this->renderFourStatuses();

        $row = $crawler->filter('[data-test-health-check="shop.a"]');
        self::assertSame('tr', $row->nodeName());
        self::assertSame('row', $row->filter('th')->attr('scope'));
        self::assertStringContainsString('shop.a', $row->filter('th')->text());
        self::assertCount(1, $crawler->filter('[data-test-health-category="shop"] details > summary'));
        self::assertCount(1, $crawler->filter('[data-test-health-category="shop"] details table'));
    }

    public function testTheActionLinkNamesTheCheckItFixes(): void
    {
        $crawler = $this->renderFourStatuses();

        self::assertSame('Fix: Seven products have no price', $crawler->filter('[data-test-health-check="shop.a"] [data-test-health-action]')->text());
    }

    public function testTheSkeletonIsABusyStatusRegion(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');

        $crawler = $this->client->request('GET', '/admin/platform-health');

        $skeleton = $crawler->filter('[data-test-health-skeleton]');
        self::assertSame('status', $skeleton->attr('role'));
        self::assertSame('true', $skeleton->attr('aria-busy'));
    }

    public function testTheResultsCarryAPoliteStatusRegionThatAnnouncesTheUpdate(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');
        $component = $this->createLiveComponent('tmonier_platform_health:summary', [], $this->client)->setRouteLocale('en_US');

        $crawler = $component->render()->crawler();

        $region = $crawler->filter('[data-test-health-announcement]');
        self::assertCount(1, $region);
        self::assertSame('status', $region->attr('role'));
        self::assertSame('polite', $region->attr('aria-live'));
        self::assertStringContainsString('Results updated', $region->text());
    }

    public function testTheRefreshButtonIsNamedAndKeepsItsIdentityAcrossTheRender(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');
        $component = $this->createLiveComponent('tmonier_platform_health:summary', [], $this->client)->setRouteLocale('en_US');

        $button = $component->render()->crawler()->filter('[data-test-health-refresh]');

        self::assertSame('Refresh', $button->text());
        self::assertSame('button', $button->attr('type'));
        // A stable id lets the DOM morphing of Live Components keep the same node, so the focus stays on it.
        self::assertSame('tmonier-platform-health-refresh', $button->attr('id'));
    }

    public function testTheStylesheetIsLoadedOncePerPageAndRespectsReducedMotion(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');

        $html = $this->client->request('GET', '/admin/platform-health')->html();

        // Declared once per theme (light, dark): the partial is included a single time.
        self::assertSame(2, substr_count($html, '--tph-surface:'));
        self::assertStringContainsString('prefers-reduced-motion: reduce', $html);
    }

    private function categoryOf(string $status): string
    {
        return ['ok' => 'environment', 'warning' => 'data', 'critical' => 'shop', 'unknown' => 'plugins'][$status];
    }

    private function renderFourStatuses(): Crawler
    {
        self::bootKernel();
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $reports = [
            new HealthCheckReport('environment.a', HealthCategory::Environment, HealthResult::ok(new TranslatableMessage('Fine')), 1.0),
            new HealthCheckReport('data.a', HealthCategory::Data, HealthResult::warning(new TranslatableMessage('Slow queue')), 1.0),
            new HealthCheckReport('shop.a', HealthCategory::Shop, HealthResult::critical(new TranslatableMessage('Seven products have no price'), action: new HealthActionLink('sylius_admin_product_index')), 1.0),
            new HealthCheckReport('plugins.a', HealthCategory::Plugins, HealthResult::unknown(new TranslatableMessage('Redis is not installed')), 1.0),
        ];

        return new Crawler($twig->render('@TmonierSyliusPlatformHealthPlugin/admin/_summary.html.twig', ['categories' => (new HealthSummaryBuilder())->build($reports)]));
    }
}
