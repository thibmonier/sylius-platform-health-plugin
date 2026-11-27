<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Http;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\Kernel;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\ProbeExecutions;

/**
 * /health on the Sylius test application: fictitious tokens (tests/TestApplication/config/config.yaml), liveness off,
 * the probes of the test application (one of them is critical) and the sixteen probes of the plugin.
 */
final class HealthEndpointTest extends WebTestCase
{
    private const PATH = '/health';

    private const LIMIT = 30;

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

    public function testWithoutHeaderTheAnswerIsAnAbsentPage(): void
    {
        $this->client->request('GET', self::PATH);
        $absent = $this->client->getResponse()->getStatusCode();
        $this->client->request('GET', '/a-page-that-does-not-exist-anywhere');

        self::assertSame(404, $absent);
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAWrongTokenAndATokenInTheUrlAreAbsentPages(): void
    {
        $this->client->request('GET', self::PATH, server: $this->bearer('fictive-global-tokenx'));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', self::PATH . '?token=fictive-global-token');
        self::assertResponseStatusCodeSame(404);
    }

    public function testTheTokenGives503AndTheGlobalStatusBecauseACheckIsCritical(): void
    {
        $this->client->request('GET', self::PATH, server: $this->bearer('fictive-global-token'));

        self::assertResponseStatusCodeSame(503);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertSame(['status', 'generated_at'], array_keys($body));
        self::assertSame('critical', $body['status']);
    }

    public function testTheDetailTokenGivesTheChecksWithoutAnyLeak(): void
    {
        $this->client->request('GET', self::PATH, server: $this->bearer('fictive-detail-token'));

        self::assertResponseStatusCodeSame(503);
        $content = (string) $this->client->getResponse()->getContent();
        $body = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertIsArray($body['checks']);
        self::assertGreaterThan(16, \count($body['checks']));
        $kernel = self::getContainer()->get('kernel');
        self::assertInstanceOf(Kernel::class, $kernel);
        foreach ([\PHP_VERSION, Kernel::VERSION, $kernel->getProjectDir(), 'sylius_channel', 'SQLSTATE', 'hunter2', '/admin', 'platform-health'] as $leak) {
            self::assertStringNotContainsString($leak, $content);
        }
    }

    public function testTheChecksAreReadFromTheCacheAfterTheFirstRequest(): void
    {
        $this->client->request('GET', self::PATH, server: $this->bearer('fictive-global-token'));
        $afterFirst = ProbeExecutions::count();
        $this->client->request('GET', self::PATH, server: $this->bearer('fictive-detail-token'));

        self::assertGreaterThan(0, $afterFirst);
        self::assertSame($afterFirst, ProbeExecutions::count());
    }

    public function testTheThirtyFirstRequestOfTheMinuteIs429WithoutRunningAnyCheck(): void
    {
        for ($call = 1; $call <= self::LIMIT; ++$call) {
            $this->client->request('GET', self::PATH, server: $this->bearer('fictive-global-token'));
            self::assertResponseStatusCodeSame(503, 'request ' . $call);
        }
        $runs = ProbeExecutions::count();

        $this->client->request('GET', self::PATH, server: $this->bearer('fictive-global-token'));

        self::assertResponseStatusCodeSame(429);
        self::assertSame('', $this->client->getResponse()->getContent());
        self::assertSame($runs, ProbeExecutions::count());
    }

    /**
     * @return array<string, string>
     */
    private function bearer(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
    }
}
