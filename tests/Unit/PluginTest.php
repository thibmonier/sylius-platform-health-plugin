<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\TmonierSyliusPlatformHealthExtension;
use Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin;

final class PluginTest extends TestCase
{
    public function testThePluginPathIsTheRootOfThePackage(): void
    {
        self::assertFileExists((new TmonierSyliusPlatformHealthPlugin())->getPath() . '/composer.json');
    }

    public function testTheExtensionAliasIsTheConfigurationPrefix(): void
    {
        self::assertSame('tmonier_sylius_platform_health', (new TmonierSyliusPlatformHealthExtension())->getAlias());
    }
}
