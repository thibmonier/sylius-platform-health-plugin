<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

/**
 * access.role is ROLE_PLATFORM_HEALTH_VIEW (tests/TestApplication/config/dedicated_role.yaml). The kernel is built with
 * debug off so that its container does not share the cache of the default test application.
 */
final class AccessControlDedicatedRoleTest extends AccessControlTestCase
{
    private ?string $previousConfig = null;

    protected function setUp(): void
    {
        $previous = $_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT'] ?? null;
        $this->previousConfig = \is_string($previous) ? $previous : null;
        $_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT'] = '../../../../tests/TestApplication/config/dedicated_role.yaml';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (null === $this->previousConfig) {
            unset($_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT']);

            return;
        }
        $_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT'] = $this->previousConfig;
    }

    protected function kernelOptions(): array
    {
        return ['debug' => false];
    }

    public function testAnAdministratorWhoCarriesTheDedicatedRoleOpensThePageAndSeesTheMenuEntry(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_PLATFORM_HEALTH_VIEW']), 'admin');

        $page = $this->client->request('GET', self::PAGE);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $page->filter('[data-live-url-value]'));

        $crawler = $this->client->request('GET', '/admin/');
        self::assertCount(1, $crawler->filter(sprintf('a[href="%s"]', self::PAGE)));
    }

    public function testAnAdministratorWhoCarriesTheDedicatedRoleRendersAndRefreshesTheComponent(): void
    {
        $request = $this->componentRequestAs(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_PLATFORM_HEALTH_VIEW']);

        $this->client->request('POST', $request['url'], ['data' => $request['data']]);
        self::assertResponseIsSuccessful();

        $this->client->request('POST', $request['url'] . '/refresh', ['data' => $request['data']]);
        self::assertResponseIsSuccessful();
    }

    public function testAnAdministratorWithoutTheRoleIsRefusedThePageWithA403(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');

        $this->client->request('GET', self::PAGE);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnAdministratorWithoutTheRoleCannotRenderTheComponentByItsOwnUrl(): void
    {
        $request = $this->componentRequestAs(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_PLATFORM_HEALTH_VIEW']);
        $this->logInAsAnotherClient(['ROLE_ADMINISTRATION_ACCESS']);

        $this->client->request('POST', $request['url'], ['data' => $request['data']]);

        self::assertResponseStatusCodeSame(403);
        self::assertStringNotContainsString('data-test-health-results', (string) $this->client->getResponse()->getContent());
    }

    public function testAnAdministratorWithoutTheRoleCannotRunTheRefreshActionByItsOwnUrl(): void
    {
        $request = $this->componentRequestAs(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_PLATFORM_HEALTH_VIEW']);
        $this->logInAsAnotherClient(['ROLE_ADMINISTRATION_ACCESS']);

        $this->client->request('POST', $request['url'] . '/refresh', ['data' => $request['data']]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnAdministratorWithoutTheRoleGetsNothingFromTheComponentWithoutAnyProps(): void
    {
        $request = $this->componentRequestAs(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_PLATFORM_HEALTH_VIEW']);
        $this->logInAsAnotherClient(['ROLE_ADMINISTRATION_ACCESS']);

        $this->client->request('POST', $request['url'] . '/refresh', ['data' => json_encode(['props' => new \stdClass()], \JSON_THROW_ON_ERROR)]);

        // Props without their checksum are refused (400) before the guard is reached: either way, an error and no result.
        self::assertContains($this->client->getResponse()->getStatusCode(), [400, 403]);
        self::assertStringNotContainsString('data-test-health-results', (string) $this->client->getResponse()->getContent());
    }

    public function testTheMenuEntryIsNotShownToAnAdministratorWithoutTheRole(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');

        $crawler = $this->client->request('GET', '/admin/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter(sprintf('a[href="%s"]', self::PAGE)));
    }
}
