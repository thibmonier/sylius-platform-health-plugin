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
        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('time_budget_ms')
                    ->info('Time budget of each probe, in milliseconds, measured by the runner after the probe returns.')
                    ->defaultValue(500)
                    ->min(1)
                ->end()
                ->integerNode('cache_ttl')
                    ->info('Seconds the results of the probes are shared between administrators (0 disables the cache). The refresh button ignores it.')
                    ->defaultValue(60)
                    ->min(0)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
