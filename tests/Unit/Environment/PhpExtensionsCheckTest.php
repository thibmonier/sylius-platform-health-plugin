<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\PhpExtensionsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class PhpExtensionsCheckTest extends TestCase
{
    use ResultAssertions;

    private const REQUIRED = ['intl', 'mbstring', 'ctype', 'iconv', 'xml', 'curl', 'pdo', 'gd'];

    public function testEveryRequiredExtensionLoadedIsOk(): void
    {
        $result = $this->execute(self::REQUIRED);

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.environment.extensions.ok');
    }

    public function testImagickInPlaceOfGdIsEnough(): void
    {
        self::assertSame(HealthStatus::Ok, $this->execute(['intl', 'mbstring', 'ctype', 'iconv', 'xml', 'curl', 'pdo', 'imagick'])->status);
    }

    public function testAMissingIntlIsCriticalAndNamedInTheDetail(): void
    {
        $result = $this->execute(array_diff(self::REQUIRED, ['intl']));

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.environment.extensions.missing');
        self::assertDetail($result, 'tmonier_platform_health.environment.extensions.detail', ['%missing%' => 'intl']);
    }

    public function testOnlyTheMissingExtensionsAreListedNeverTheLoadedOnes(): void
    {
        $result = $this->execute(array_diff(self::REQUIRED, ['intl', 'curl']));

        self::assertDetail($result, 'tmonier_platform_health.environment.extensions.detail', ['%missing%' => 'intl, curl']);
        self::assertStringNotContainsString('mbstring', self::serialized($result));
    }

    public function testNeitherGdNorImagickIsReportedAsAnImageExtension(): void
    {
        $result = $this->execute(array_diff(self::REQUIRED, ['gd']));

        self::assertDetail($result, 'tmonier_platform_health.environment.extensions.detail', ['%missing%' => 'gd|imagick']);
    }

    public function testWithoutAClosureItReadsTheRunningPhpWithoutAnException(): void
    {
        $status = (new PhpExtensionsCheck())->run()->status;

        self::assertContains($status, [HealthStatus::Ok, HealthStatus::Critical]);
    }

    public function testItCanNeverBeUnknownBecauseAnExtensionIsEitherLoadedOrNot(): void
    {
        self::assertNotSame(HealthStatus::Unknown, $this->execute([])->status);
    }

    /**
     * @param array<string> $loaded
     */
    private function execute(array $loaded): HealthResult
    {
        return (new PhpExtensionsCheck(static fn (string $extension): bool => \in_array($extension, $loaded, true)))->run();
    }
}
