<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\FailingClock;

/**
 * A panel that fails disappears without noise: the dashboard stays intact (the log is checked by the unit test of the component).
 */
final class DashboardPanelFailureTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $cache = self::getContainer()->get('cache.app');
        self::assertInstanceOf(AdapterInterface::class, $cache);
        $cache->clear();
        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $admin = $registry->getRepository(AdminUserInterface::class)->findOneBy(['username' => 'sylius']);
        self::assertInstanceOf(AdminUserInterface::class, $admin);
        $this->client->loginUser($admin, 'admin');
        FailingClock::failFromNowOn();
    }

    protected function tearDown(): void
    {
        FailingClock::recover();
        parent::tearDown();
    }

    public function testTheDashboardIsDisplayedAndThePanelCompletesWithNothing(): void
    {
        $dashboard = $this->client->request('GET', '/admin/');
        self::assertResponseIsSuccessful();
        $placeholder = $dashboard->filter('[data-live-name-value="tmonier_platform_health:dashboard"]');
        self::assertCount(1, $placeholder);

        $props = json_decode((string) $placeholder->attr('data-live-props-value'), true, flags: \JSON_THROW_ON_ERROR);
        $panel = $this->client->request('POST', (string) $placeholder->attr('data-live-url-value'), ['data' => json_encode(['props' => $props], \JSON_THROW_ON_ERROR)]);

        self::assertResponseIsSuccessful();
        self::assertCount(0, $panel->filter('[data-test-health-dashboard-category]'));
        self::assertCount(0, $panel->filter('[data-test-health-dashboard-panel]'));
        self::assertStringNotContainsString(FailingClock::MESSAGE, (string) $this->client->getResponse()->getContent());
    }
}
