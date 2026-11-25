<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

/**
 * No access.role configured: the behaviour of v0.1.0, every administrator is let in.
 */
final class AccessControlDefaultTest extends AccessControlTestCase
{
    public function testAnAdministratorWithOnlyTheAdministrationRoleOpensThePageAndRefreshesTheComponent(): void
    {
        $request = $this->componentRequestAs(['ROLE_ADMINISTRATION_ACCESS']);

        $this->client->request('POST', $request['url'], ['data' => $request['data']]);
        self::assertResponseIsSuccessful();

        $this->client->request('POST', $request['url'] . '/refresh', ['data' => $request['data']]);
        self::assertResponseIsSuccessful();
    }

    public function testTheMenuEntryIsShownToThatAdministrator(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');

        $crawler = $this->client->request('GET', '/admin/');

        self::assertCount(1, $crawler->filter(sprintf('a[href="%s"]', self::PAGE)));
    }

    public function testTheDashboardShowsThePanelToThatAdministrator(): void
    {
        $this->client->loginUser($this->createAdministrator(['ROLE_ADMINISTRATION_ACCESS']), 'admin');

        $dashboard = $this->client->request('GET', self::DASHBOARD);

        self::assertCount(1, $dashboard->filter(self::PANEL));
    }
}
