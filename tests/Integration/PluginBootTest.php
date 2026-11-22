<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin;

final class PluginBootTest extends KernelTestCase
{
    public function testThePluginIsRegisteredInTheTestApplication(): void
    {
        $kernel = self::bootKernel();

        self::assertArrayHasKey(TmonierSyliusPlatformHealthPlugin::class, $this->bundleClasses($kernel->getBundles()));
    }

    /**
     * @param array<string, object> $bundles
     *
     * @return array<class-string, true>
     */
    private function bundleClasses(array $bundles): array
    {
        $classes = [];
        foreach ($bundles as $bundle) {
            $classes[$bundle::class] = true;
        }

        return $classes;
    }
}
