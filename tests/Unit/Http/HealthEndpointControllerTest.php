<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Http;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Http\HealthEndpointController;

/**
 * One test per line of the table of cases of US-308. The tokens are made up for the test and composed at run time.
 */
final class HealthEndpointControllerTest extends TestCase
{
    private const LIMIT = 30;

    private string $global;

    private string $detail;

    private int $runs = 0;

    protected function setUp(): void
    {
        $this->global = 'fictive-' . 'global-' . 'token';
        $this->detail = 'fictive-' . 'detail-' . 'token';
        $this->runs = 0;
    }

    public function testWithoutAnyConfiguredTokenTheRouteIsInactive(): void
    {
        $controller = $this->controller([HealthStatus::Ok], tokens: false);

        $this->expectException(NotFoundHttpException::class);
        $controller($this->request($this->global));
    }

    public function testWithoutAnyConfiguredTokenLivenessDoesNotActivateTheRoute(): void
    {
        $controller = $this->controller([HealthStatus::Ok], tokens: false, liveness: true);

        $this->expectException(NotFoundHttpException::class);
        $controller($this->request(null));
    }

    public function testWithoutHeaderAndWithoutLivenessTheAnswerIsAnAbsentPage(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->controller([HealthStatus::Ok])($this->request(null));
    }

    public function testWithoutHeaderAndWithLivenessTheAnswerIs200WithAnEmptyBodyWhenNothingIsCritical(): void
    {
        $response = $this->controller([HealthStatus::Ok, HealthStatus::Warning], liveness: true)($this->request(null));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testWithoutHeaderAndWithLivenessTheAnswerIs503WithAnEmptyBodyWhenACheckIsCritical(): void
    {
        $response = $this->controller([HealthStatus::Ok, HealthStatus::Critical], liveness: true)($this->request(null));

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testAWrongTokenIsAnAbsentPage(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->controller([HealthStatus::Ok], liveness: true)($this->request($this->global . 'x'));
    }

    public function testATokenInTheUrlIsAnAbsentPage(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->controller([HealthStatus::Ok])($this->request(null, ['token' => $this->global]));
    }

    public function testATokenInTheUrlIsAnAbsentPageEvenWithLiveness(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->controller([HealthStatus::Ok], liveness: true)($this->request(null, ['token' => $this->global]));
    }

    public function testTheTokenGives200AndTheGlobalStatusOnly(): void
    {
        $response = $this->controller([HealthStatus::Ok])($this->request($this->global));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status', 'generated_at'], array_keys($this->json($response)));
        self::assertSame('ok', $this->json($response)['status']);
    }

    public function testTheTokenGives503WithTheSameBodyWhenACheckIsCritical(): void
    {
        $response = $this->controller([HealthStatus::Ok, HealthStatus::Critical])($this->request($this->global));

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(['status', 'generated_at'], array_keys($this->json($response)));
        self::assertSame('critical', $this->json($response)['status']);
    }

    public function testTheDetailTokenGivesTheChecksWithoutTheirSummary(): void
    {
        $response = $this->controller([HealthStatus::Ok, HealthStatus::Critical])($this->request($this->detail));

        self::assertSame(503, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame(['status', 'generated_at', 'checks'], array_keys($body));
        self::assertIsArray($body['checks']);
        self::assertCount(2, $body['checks']);
        self::assertSame(['identifier', 'category', 'status'], array_keys((array) $body['checks'][0]));
    }

    public function testTheDetailTokenGives200WhenNothingIsCritical(): void
    {
        $response = $this->controller([HealthStatus::Ok])($this->request($this->detail));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testTheThirtiethRequestOfTheMinutePassesAndTheThirtyFirstIs429WithoutRunningAnyCheck(): void
    {
        $controller = $this->controller([HealthStatus::Ok], liveness: true);
        for ($call = 1; $call <= self::LIMIT; ++$call) {
            self::assertSame(200, $controller($this->request(null))->getStatusCode(), 'request ' . $call);
        }
        $runsBefore = $this->runs;

        $response = $controller($this->request($this->global));

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('', $response->getContent());
        self::assertSame($runsBefore, $this->runs);
    }

    public function testTheLimitCountsRequestsWhateverTheirAnswer(): void
    {
        $controller = $this->controller([HealthStatus::Ok]);
        for ($call = 1; $call <= self::LIMIT; ++$call) {
            try {
                $controller($this->request('wrong'));
            } catch (NotFoundHttpException) {
            }
        }

        self::assertSame(429, $controller($this->request($this->global))->getStatusCode());
    }

    public function testEachAddressHasItsOwnCount(): void
    {
        $controller = $this->controller([HealthStatus::Ok], liveness: true);
        for ($call = 1; $call <= self::LIMIT; ++$call) {
            $controller($this->request(null));
        }

        self::assertSame(200, $controller($this->request(null, [], '203.0.113.9'))->getStatusCode());
    }

    public function testWarningAndUnknownAloneGive200(): void
    {
        $controller = $this->controller([HealthStatus::Warning, HealthStatus::Unknown]);

        $response = $controller($this->request($this->global));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('warning', $this->json($response)['status']);
    }

    public function testUnknownAloneIsReportedAsUnknownWith200(): void
    {
        $response = $this->controller([HealthStatus::Unknown])($this->request($this->global));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('unknown', $this->json($response)['status']);
    }

    public function testTheAnswersCarryNoCacheHeader(): void
    {
        $response = $this->controller([HealthStatus::Ok])($this->request($this->global));

        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function testAnswersLeakNeitherVersionNorPathNorTableNorExceptionNorDetailNorLink(): void
    {
        $leaks = ['9.9.9-secret', '/var/www/shop/secret', 'sylius_secret_table', 'SQLSTATE[HY000]', 'hunter2', 'detail-secret', 'sylius_admin_secret_route'];
        $controller = $this->controllerWith([
            new FakeCheck('environment.leaky', HealthCategory::Environment, static fn (): HealthResult => HealthResult::warning(
                new TranslatableMessage('PHP 9.9.9-secret in /var/www/shop/secret, table sylius_secret_table'),
                new TranslatableMessage('detail-secret'),
                new \Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink('sylius_admin_secret_route'),
            )),
            new FakeCheck('data.throwing', HealthCategory::Data, static fn (): HealthResult => throw new \RuntimeException('SQLSTATE[HY000] mysql://root:hunter2@db')),
        ]);

        foreach ([$this->global, $this->detail] as $token) {
            $response = $controller($this->request($token, [], '198.51.100.' . ($token === $this->global ? 1 : 2)));
            self::assertContains($response->getStatusCode(), [200, 503]);
            $content = (string) $response->getContent();
            foreach ($leaks as $leak) {
                self::assertStringNotContainsString($leak, $content);
            }
        }
    }

    /**
     * @param list<HealthStatus> $statuses
     */
    private function controller(array $statuses, bool $tokens = true, bool $liveness = false): HealthEndpointController
    {
        $checks = [];
        foreach ($statuses as $index => $status) {
            $checks[] = new FakeCheck('data.check_' . $index, HealthCategory::Data, function () use ($status): HealthResult {
                ++$this->runs;

                return new HealthResult($status, new TranslatableMessage('Summary'));
            });
        }

        return $this->controllerWith($checks, $tokens, $liveness);
    }

    /**
     * @param list<FakeCheck> $checks
     */
    private function controllerWith(array $checks, bool $tokens = true, bool $liveness = false): HealthEndpointController
    {
        $logger = new NullLogger();
        $snapshots = new HealthSnapshotProvider(new HealthCheckRunner($checks, $logger), new ArrayAdapter(), new MockClock('2026-11-27 10:00:00', 'UTC'), $logger, 60);
        $limiter = new RateLimiterFactory(['id' => 'test', 'policy' => 'fixed_window', 'limit' => self::LIMIT, 'interval' => '1 minute'], new InMemoryStorage());

        return new HealthEndpointController($snapshots, $limiter, $tokens ? $this->global : null, $tokens ? $this->detail : null, $liveness);
    }

    /**
     * @param array<string, string> $query
     */
    private function request(?string $bearer, array $query = [], string $ip = '203.0.113.1'): Request
    {
        $request = Request::create('/health', 'GET', $query, [], [], ['REMOTE_ADDR' => $ip]);
        if (null !== $bearer) {
            $request->headers->set('Authorization', 'Bearer ' . $bearer);
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        $body = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        /** @var array<string, mixed> $typed */
        $typed = $body;

        return $typed;
    }
}
