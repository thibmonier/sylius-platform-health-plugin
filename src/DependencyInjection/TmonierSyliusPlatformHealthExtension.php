<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class TmonierSyliusPlatformHealthExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->processConfiguration(new Configuration(), $configs);
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_platform_health';
    }
}
