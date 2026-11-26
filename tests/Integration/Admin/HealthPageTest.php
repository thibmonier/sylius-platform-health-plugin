<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\BrokenCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\FailingCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\ProbeExecutions;

/**
 * The administration page, on the Sylius test application, with the five probes of tests/TestApplication
 * (one ok, one warning, one critical, one unknown, one that throws), next to the six environment probes of the plugin
 * (their clock is fixed by the test application: the page never depends on the date of the day).
 */
final class HealthPageTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    private const PAGE = '/admin/platform-health';

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
    }

    public function testTheSkeletonIsRenderedWithoutRunningAnyCheck(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $crawler = $this->client->request('GET', self::PAGE);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-live-url-value]'));
        self::assertCount(0, $crawler->filter('[data-test-health-category]'));
        self::assertSame(0, ProbeExecutions::count());
    }

    public function testTheResultsComeWithTheDeferredRequestOfTheBrowser(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());
        self::assertCount(5, $results->filter('[data-test-health-category]'));
    }

    public function testCategoriesAreOrderedMostSevereFirstEachWithItsWorstStatus(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertSame(
            ['shop:critical', 'environment:warning', 'data:warning', 'scheduled_tasks:unknown', 'plugins:unknown'],
            $results->filter('[data-test-health-category]')->each(static fn (Crawler $category): string => $category->attr('data-test-health-category') . ':' . $category->attr('data-test-health-status')),
        );
    }

    public function testTheActionLinkOfACheckOpensTheRouteItNames(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertCount(1, $results->filter('[data-test-health-check="shop.demo"] [data-test-health-action]'));
        self::assertSame('/admin/payment-methods/?page=1', $results->filter('[data-test-health-check="shop.demo"] [data-test-health-action]')->attr('href'));
        self::assertCount(0, $results->filter('[data-test-health-check="data.demo"] [data-test-health-action]'));
    }

    public function testAnUnmeasurableCheckIsReadableAndItsCategoryIsNotCritical(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertCount(1, $results->filter('[data-test-health-check="plugins.demo"]'));
        self::assertStringContainsString('Not measurable', $results->filter('[data-test-health-check="plugins.demo"]')->text());
        self::assertStringContainsString('Demo probe: the extension is not installed', $results->filter('[data-test-health-check="plugins.demo"]')->text());
        self::assertCount(1, $results->filter('[data-test-health-category="plugins"]'));
        self::assertSame('unknown', $results->filter('[data-test-health-category="plugins"]')->attr('data-test-health-status'));
    }

    public function testAFailingCheckNeverBreaksThePageNorLeaksItsMessage(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertCount(1, $results->filter('[data-test-health-check="plugins.failing"]'));
        self::assertStringContainsString('The check could not be completed', $results->filter('[data-test-health-check="plugins.failing"]')->text());
        self::assertStringNotContainsString('hunter2', (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString(FailingCheck::SECRET, (string) $this->client->getResponse()->getContent());
    }

    public function testTheProbeOfAnotherPluginIsListedInItsCategory(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        $row = $results->filter('[data-test-health-category="plugins"] [data-test-health-check="demo_webhooks.outbox"]');
        self::assertCount(1, $row);
        self::assertSame('ok', $row->attr('data-test-health-status'));
    }

    public function testAProbeOfAnotherPluginThatThrowsIsUnknownAndTheOtherProbesAreStillShown(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertCount(1, $results->filter('[data-test-health-check="demo_webhooks.broken"]'));
        self::assertSame('unknown', $results->filter('[data-test-health-check="demo_webhooks.broken"]')->attr('data-test-health-status'));
        self::assertStringNotContainsString(BrokenCheck::MESSAGE, (string) $this->client->getResponse()->getContent());
        self::assertCount(1, $results->filter('[data-test-health-check="demo_webhooks.outbox"]'));
        self::assertCount(1, $results->filter('[data-test-health-check="shop.demo"]'));
    }

    public function testASecondAdministratorWithinTheTtlGetsTheCachedResultsAndRunsNoCheck(): void
    {
        $this->client->loginUser($this->admin(), 'admin');
        $this->deferredResults($this->client->request('GET', self::PAGE));
        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());

        self::ensureKernelShutdown();
        $secondAdmin = self::createClient();
        $secondAdmin->loginUser($this->admin(), 'admin');
        $this->client = $secondAdmin;
        $results = $this->deferredResults($secondAdmin->request('GET', self::PAGE));

        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());
        self::assertCount(5, $results->filter('[data-test-health-category]'));
    }

    public function testTheRefreshActionRunsTheChecksAgainAndReplacesTheCache(): void
    {
        $this->client->loginUser($this->admin(), 'admin');
        // Some Sylius/Symfony combinations (6.4) generate the component route with a mandatory locale.
        $component = $this->createLiveComponent('tmonier_platform_health:summary', [], $this->client)->setRouteLocale('en_US');
        $component->render();
        self::assertSame(self::FIVE_PROBES, ProbeExecutions::count());

        $component->call('refresh');
        self::assertSame(2 * self::FIVE_PROBES, ProbeExecutions::count());

        $this->deferredResults($this->client->request('GET', self::PAGE));
        self::assertSame(2 * self::FIVE_PROBES, ProbeExecutions::count());
    }

    public function testTheRefreshButtonIsOnTheResults(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $results = $this->deferredResults($this->client->request('GET', self::PAGE));

        self::assertCount(1, $results->filter('[data-test-health-refresh]'));
        self::assertSame('refresh', $results->filter('[data-test-health-refresh]')->attr('data-live-action-param'));
    }

    public function testThePageIsInTheConfigurationMenu(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $crawler = $this->client->request('GET', '/admin/');

        self::assertCount(1, $crawler->filter(sprintf('a[href="%s"]', self::PAGE)));
        self::assertStringContainsString('Platform health', $crawler->filter(sprintf('a[href="%s"]', self::PAGE))->text());
    }

    public function testTheTitleOfThePageIsTranslated(): void
    {
        $this->client->loginUser($this->admin(), 'admin');

        $this->client->request('GET', self::PAGE);

        self::assertSelectorExists('[data-test-platform-health-header]');
        self::assertSelectorTextContains('[data-test-platform-health-header]', 'Platform health');
    }

    public function testAnAnonymousVisitorIsSentToTheLogin(): void
    {
        $this->client->request('GET', self::PAGE);

        self::assertResponseRedirects('http://localhost/admin/login');
        self::assertSame(0, ProbeExecutions::count());
    }

    /**
     * What the browser does after the skeleton: it posts the props of the skeleton to the component URL (a Live Component answers a render by POST).
     */
    private function deferredResults(Crawler $skeleton): Crawler
    {
        $placeholder = $skeleton->filter('[data-live-url-value]');
        self::assertCount(1, $placeholder);

        $url = (string) $placeholder->attr('data-live-url-value');
        $props = (string) $placeholder->attr('data-live-props-value');

        return $this->client->request('POST', $url, ['data' => json_encode(['props' => json_decode($props, true, flags: \JSON_THROW_ON_ERROR)], \JSON_THROW_ON_ERROR)]);
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
