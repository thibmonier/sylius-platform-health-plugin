<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\OpcacheCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\OpcacheState;
use Tmonier\SyliusPlatformHealthPlugin\Environment\OpcacheStateReaderInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class OpcacheCheckTest extends TestCase
{
    use ResultAssertions;

    public function testAnActiveOpcacheIsOk(): void
    {
        self::assertSummary($this->execute(OpcacheState::Active), HealthStatus::Ok, 'tmonier_platform_health.environment.opcache.ok');
    }

    public function testAnInactiveOpcacheWarns(): void
    {
        self::assertSummary($this->execute(OpcacheState::Inactive), HealthStatus::Warning, 'tmonier_platform_health.environment.opcache.inactive');
    }

    public function testARestrictedApiIsUnknownNeverOkNorWarning(): void
    {
        self::assertSummary($this->execute(OpcacheState::Restricted), HealthStatus::Unknown, 'tmonier_platform_health.environment.opcache.restricted');
    }

    public function testTheNativeReaderAnswersWithoutAnExceptionInTheCurrentSapi(): void
    {
        $state = (new \Tmonier\SyliusPlatformHealthPlugin\Environment\NativeOpcacheStateReader())->read();

        self::assertContains($state, OpcacheState::cases());
    }

    public function testTheNativeReaderReadsAnInactiveOpcacheInTheCli(): void
    {
        if (\PHP_SAPI !== 'cli' || filter_var(ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOLEAN)) {
            self::markTestSkipped('OPcache is active in this CLI.');
        }

        self::assertSame(OpcacheState::Inactive, (new \Tmonier\SyliusPlatformHealthPlugin\Environment\NativeOpcacheStateReader())->read());
    }

    private function execute(OpcacheState $state): HealthResult
    {
        return (new OpcacheCheck(new class($state) implements OpcacheStateReaderInterface {
            public function __construct(private readonly OpcacheState $state)
            {
            }

            public function read(): OpcacheState
            {
                return $this->state;
            }
        }))->run();
    }
}
