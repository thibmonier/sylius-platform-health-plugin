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
                ->arrayNode('access')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('role')
                            ->info('Role that the PLATFORM_HEALTH_VIEW attribute requires. Use ROLE_PLATFORM_HEALTH_VIEW in production.')
                            ->defaultValue('ROLE_ADMINISTRATION_ACCESS')
                            ->validate()
                                ->ifTrue(static fn (mixed $role): bool => !\is_string($role) || 1 !== preg_match('/^ROLE_\S+$/', $role))
                                ->thenInvalid('The access role must be a Symfony role, starting with "ROLE_" (for instance "ROLE_PLATFORM_HEALTH_VIEW"), got %s.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('environment')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('support_warning_days')
                            ->info('Days before the end of support of PHP, Symfony or Sylius from which the environment probes warn.')
                            ->defaultValue(180)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('data')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('connection_warning_ms')
                            ->info('Milliseconds from which the answer of the database to a trivial query warns.')
                            ->defaultValue(500)
                            ->min(1)
                        ->end()
                        ->arrayNode('table_growth')
                            ->info('Tables to watch, with the estimated rows from which each warns (critical beyond the double). None by default.')
                            ->useAttributeAsKey('table')
                            ->normalizeKeys(false)
                            ->integerPrototype()->min(1)->end()
                            ->validate()
                                ->ifTrue(static fn (array $tables): bool => [] !== array_filter(array_keys($tables), static fn (int|string $table): bool => 1 !== preg_match('/^[A-Za-z0-9_]+$/', (string) $table)))
                                ->thenInvalid('A watched table is named with letters, digits and underscores only (for instance "sylius_order"), got %s.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
