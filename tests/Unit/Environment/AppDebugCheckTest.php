<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\AppDebugCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class AppDebugCheckTest extends TestCase
{
    use ResultAssertions;

    public function testProductionWithoutDebugIsOk(): void
    {
        $result = (new AppDebugCheck(false, 'prod'))->run();

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.environment.app_debug.ok');
    }

    public function testDebugActiveInProductionIsCritical(): void
    {
        $result = (new AppDebugCheck(true, 'prod'))->run();

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.environment.app_debug.debug_in_prod');
    }

    public function testTheCriticalSummaryCarriesNeitherAnEnvironmentVariableValueNorAServerPath(): void
    {
        $result = (new AppDebugCheck(true, 'prod'))->run();

        $text = self::serialized($result);
        self::assertStringNotContainsString('/', $text);
        self::assertStringNotContainsString('APP_SECRET', $text);
        self::assertStringNotContainsString((string) getcwd(), $text);
    }

    public function testADevelopmentEnvironmentWarnsEvenWithoutDebug(): void
    {
        $result = (new AppDebugCheck(false, 'dev'))->run();

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.app_debug.not_prod', ['%environment%' => 'dev']);
    }

    public function testAnUnusualEnvironmentNameIsNeverRendered(): void
    {
        $result = (new AppDebugCheck(false, 'secret-staging-name'))->run();

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.app_debug.not_prod', ['%environment%' => 'other']);
        self::assertStringNotContainsString('secret-staging-name', self::serialized($result));
    }

    public function testAProductionWithDebugOffHasNoActionLinkAsThereIsNothingToFix(): void
    {
        self::assertNull((new AppDebugCheck(false, 'prod'))->run()->action);
        self::assertNotNull((new AppDebugCheck(true, 'prod'))->run()->action);
    }
}
