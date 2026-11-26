<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    public function testTheAccessRoleDefaultsToTheRoleOfEveryAdministrator(): void
    {
        self::assertSame('ROLE_ADMINISTRATION_ACCESS', $this->process([])['access']['role']);
    }

    public function testTheDedicatedRoleCanBeConfigured(): void
    {
        self::assertSame('ROLE_PLATFORM_HEALTH_VIEW', $this->process(['access' => ['role' => 'ROLE_PLATFORM_HEALTH_VIEW']])['access']['role']);
    }

    #[DataProvider('notARole')]
    public function testAValueThatIsNotARoleIsRefusedWithAClearMessage(string $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/ROLE_/');

        $this->process(['access' => ['role' => $value]]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notARole(): iterable
    {
        yield 'no prefix' => ['PLATFORM_HEALTH_VIEW'];
        yield 'lower case' => ['role_platform_health_view'];
        yield 'empty' => [''];
        yield 'prefix only' => ['ROLE_'];
    }

    public function testTheDataProbesHaveOpinionatedDefaultsAndWatchNoTable(): void
    {
        $data = $this->process([])['data'];

        self::assertSame(500, $data['connection_warning_ms']);
        self::assertSame([], $data['table_growth']);
    }

    public function testTheQueueProbesHaveOpinionatedDefaults(): void
    {
        self::assertSame(
            ['pending_warning' => 100, 'oldest_warning_minutes' => 15, 'oldest_critical_minutes' => 60, 'failed_critical' => 100],
            $this->process([])['data']['messenger'],
        );
    }

    public function testTheCriticalAgeMustBeAboveTheWarningAge(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/oldest_critical_minutes/');

        $this->process(['data' => ['messenger' => ['oldest_warning_minutes' => 30, 'oldest_critical_minutes' => 30]]]);
    }

    public function testWatchedTablesKeepTheirNameAndThreshold(): void
    {
        $data = $this->process(['data' => ['table_growth' => ['tmonier_audit_log' => 100000, 'sylius_order' => 500000]]])['data'];

        self::assertSame(['tmonier_audit_log' => 100000, 'sylius_order' => 500000], $data['table_growth']);
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidData')]
    public function testInvalidDataSettingsAreRefused(array $data): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['data' => $data]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidData(): iterable
    {
        yield 'zero threshold' => [['table_growth' => ['tmonier_audit_log' => 0]]];
        yield 'table name with a quote' => [['table_growth' => ["audit'; --" => 1000]]];
        yield 'zero connection threshold' => [['connection_warning_ms' => 0]];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{access: array{role: string}, data: array{connection_warning_ms: int, messenger: array<string, int>, table_growth: array<string, int>}}
     */
    private function process(array $config): array
    {
        /** @var array{access: array{role: string}, data: array{connection_warning_ms: int, messenger: array<string, int>, table_growth: array<string, int>}} $processed */
        $processed = (new Processor())->processConfiguration(new Configuration(), [$config]);

        return $processed;
    }
}
