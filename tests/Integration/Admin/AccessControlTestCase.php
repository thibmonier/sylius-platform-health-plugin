<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\CreatesAdministrators;

/**
 * Shared by the two settings of access.role. The component is reached the way an administrator who is refused the
 * page would reach it: by its own URL, under /admin/{_locale}/_components/, which the access_control of the
 * administration only protects with ROLE_ADMINISTRATION_ACCESS (OWASP A01).
 */
abstract class AccessControlTestCase extends WebTestCase
{
    use CreatesAdministrators;

    protected const PAGE = '/admin/platform-health';

    protected const DASHBOARD = '/admin/';

    protected const PANEL = '[data-live-name-value="tmonier_platform_health:dashboard"]';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient($this->kernelOptions());
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

    /**
     * @return array<string, mixed>
     */
    protected function kernelOptions(): array
    {
        return [];
    }

    /**
     * @param list<string> $roles
     *
     * @return array{url: string, data: string}
     */
    protected function componentRequestAs(array $roles): array
    {
        $this->client->loginUser($this->createAdministrator($roles), 'admin');
        $skeleton = $this->client->request('GET', self::PAGE);
        self::assertResponseIsSuccessful();

        return $this->componentRequestOf($skeleton);
    }

    /**
     * @return array{url: string, data: string}
     */
    protected function componentRequestOf(Crawler $skeleton): array
    {
        $placeholder = $skeleton->filter('[data-live-url-value]');
        self::assertCount(1, $placeholder);
        $props = json_decode((string) $placeholder->attr('data-live-props-value'), true, flags: \JSON_THROW_ON_ERROR);

        return ['url' => (string) $placeholder->attr('data-live-url-value'), 'data' => json_encode(['props' => $props], \JSON_THROW_ON_ERROR)];
    }

    /**
     * The dashboard panel (US-289), reached the same way: by the URL of its component.
     *
     * @param list<string> $roles
     *
     * @return array{url: string, data: string}
     */
    protected function panelRequestAs(array $roles): array
    {
        $this->client->loginUser($this->createAdministrator($roles), 'admin');
        $dashboard = $this->client->request('GET', self::DASHBOARD);
        self::assertResponseIsSuccessful();
        $placeholder = $dashboard->filter(self::PANEL);
        self::assertCount(1, $placeholder);
        $props = json_decode((string) $placeholder->attr('data-live-props-value'), true, flags: \JSON_THROW_ON_ERROR);

        return ['url' => (string) $placeholder->attr('data-live-url-value'), 'data' => json_encode(['props' => $props], \JSON_THROW_ON_ERROR)];
    }

    /**
     * @param list<string> $roles
     */
    protected function logInAsAnotherClient(array $roles): void
    {
        $administrator = $this->createAdministrator($roles);
        self::ensureKernelShutdown();
        $this->client = self::createClient($this->kernelOptions());
        $this->client->disableReboot();
        $this->client->loginUser($administrator, 'admin');
    }
}
