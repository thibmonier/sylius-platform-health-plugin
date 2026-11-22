<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tmonier_sylius_platform_health');
        $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
