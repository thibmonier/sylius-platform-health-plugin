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

    /**
     * @param array<string, mixed> $config
     *
     * @return array{access: array{role: string}}
     */
    private function process(array $config): array
    {
        /** @var array{access: array{role: string}} $processed */
        $processed = (new Processor())->processConfiguration(new Configuration(), [$config]);

        return $processed;
    }
}
