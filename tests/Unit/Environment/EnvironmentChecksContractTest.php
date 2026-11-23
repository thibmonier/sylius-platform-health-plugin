<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Tmonier\SyliusPlatformHealthPlugin\Environment\AppDebugCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\OpcacheCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\PhpExtensionsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\PhpVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SyliusVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SymfonyVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

final class EnvironmentChecksContractTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<HealthCheckInterface>, string}>
     */
    public static function checks(): iterable
    {
        yield 'php' => [PhpVersionCheck::class, 'environment.php_version'];
        yield 'symfony' => [SymfonyVersionCheck::class, 'environment.symfony_version'];
        yield 'sylius' => [SyliusVersionCheck::class, 'environment.sylius_version'];
        yield 'extensions' => [PhpExtensionsCheck::class, 'environment.php_extensions'];
        yield 'opcache' => [OpcacheCheck::class, 'environment.opcache'];
        yield 'debug' => [AppDebugCheck::class, 'environment.app_debug'];
    }

    /**
     * @param class-string<HealthCheckInterface> $class
     */
    #[DataProvider('checks')]
    public function testTheIdentifierIsAConstantReadWithoutTheConstructor(string $class, string $identifier): void
    {
        $check = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        self::assertSame($identifier, $check->identifier());
        self::assertSame(HealthCategory::Environment, $check->category());
    }

    /**
     * @param class-string<HealthCheckInterface> $class
     */
    #[DataProvider('checks')]
    public function testTheIdentifierIsALiteralNotBuiltFromAnything(string $class): void
    {
        $method = new \ReflectionMethod($class, 'identifier');
        $lines = \array_slice((array) file((string) $method->getFileName()), (int) $method->getStartLine(), (int) $method->getEndLine() - (int) $method->getStartLine());

        self::assertMatchesRegularExpression("/return '[a-z_.]+';/", implode('', $lines));
    }

    public function testEveryTranslationKeyIsDeclaredInEnglishAndFrench(): void
    {
        $keys = [
            'environment.support.ok', 'environment.support.warning', 'environment.support.critical', 'environment.support.branch_missing',
            'environment.support.unreadable', 'environment.support.table_stale', 'environment.support.maintenance_ended',
            'environment.support.detail.table', 'environment.support.detail.kernel',
            'environment.extensions.ok', 'environment.extensions.missing', 'environment.extensions.detail',
            'environment.opcache.ok', 'environment.opcache.inactive', 'environment.opcache.restricted',
            'environment.app_debug.ok', 'environment.app_debug.debug_in_prod', 'environment.app_debug.not_prod',
        ];

        foreach (['en', 'fr'] as $locale) {
            /** @var array{tmonier_platform_health: array<string, mixed>} $catalogue */
            $catalogue = Yaml::parseFile(__DIR__ . '/../../../translations/messages.' . $locale . '.yaml');
            foreach ($keys as $key) {
                $value = $catalogue['tmonier_platform_health'];
                foreach (explode('.', $key) as $segment) {
                    self::assertIsArray($value, $locale . ': ' . $key);
                    self::assertArrayHasKey($segment, $value, $locale . ': ' . $key);
                    $value = $value[$segment];
                }
                self::assertIsString($value, $locale . ': ' . $key);
            }
        }
    }
}
