<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\BuildsHealthContainer;
use Tmonier\SyliusPlatformHealthPlugin\Http\HealthEndpointController;

/**
 * The configuration of http.* reaches the controller: a plain Symfony container, no Sylius kernel.
 */
final class HttpEntryPointWiringTest extends TestCase
{
    use BuildsHealthContainer;

    public function testWithoutTokenTheRouteIsInactiveEvenWithLiveness(): void
    {
        $controller = $this->controllerFor(['liveness' => true]);

        $this->expectException(NotFoundHttpException::class);
        $controller(Request::create('/health'));
    }

    public function testWithLivenessAndATokenAHeaderlessRequestGetsAnEmptyAnswer(): void
    {
        $response = $this->controllerFor(['token' => 'fictive-wiring-token', 'liveness' => true])(Request::create('/health'));

        self::assertContains($response->getStatusCode(), [200, 503]);
        self::assertSame('', $response->getContent());
    }

    public function testWithoutLivenessAHeaderlessRequestIsAnAbsentPage(): void
    {
        $controller = $this->controllerFor(['token' => 'fictive-wiring-token']);

        $this->expectException(NotFoundHttpException::class);
        $controller(Request::create('/health'));
    }

    public function testTheRateLimitComesFromTheConfiguration(): void
    {
        $controller = $this->controllerFor(['token' => 'fictive-wiring-token', 'liveness' => true, 'rate_limit' => 2]);

        $statuses = [];
        foreach ([1, 2, 3] as $ignored) {
            $statuses[] = $controller(Request::create('/health', server: ['REMOTE_ADDR' => '203.0.113.5']))->getStatusCode();
        }

        self::assertSame(429, $statuses[2]);
        self::assertNotSame(429, $statuses[1]);
    }

    /**
     * @param array<string, mixed> $http
     */
    private function controllerFor(array $http): HealthEndpointController
    {
        $container = $this->container();
        $container->loadFromExtension('tmonier_sylius_platform_health', ['http' => $http]);
        $container->setAlias('test.http_controller', 'tmonier_sylius_platform_health.controller.http')->setPublic(true);
        $this->compile($container);

        $controller = $container->get('test.http_controller');
        self::assertInstanceOf(HealthEndpointController::class, $controller);

        return $controller;
    }

    private function compile(ContainerBuilder $container): void
    {
        $container->compile();
    }
}
