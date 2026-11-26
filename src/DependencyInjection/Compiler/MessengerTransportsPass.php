<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Hands the Messenger transports to the queue probes (US-295): the receivers, the DSN expression of each transport (the
 * container resolves its environment variables at runtime) and the failure transport FrameworkBundle gave each one.
 */
final class MessengerTransportsPass implements CompilerPassInterface
{
    public const QUEUES = 'tmonier_sylius_platform_health.data.messenger_queues';

    private const FAILURE_LISTENER = 'messenger.failure.send_failed_message_to_failure_transport_listener';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::QUEUES)) {
            return;
        }

        $transports = [];
        $receivers = [];
        foreach ($container->findTaggedServiceIds('messenger.receiver') as $id => $tags) {
            foreach ($tags as $tag) {
                if (!\is_array($tag)) {
                    continue;
                }
                $name = $tag['alias'] ?? null;
                $dsn = $container->findDefinition($id)->getArguments()[0] ?? null;
                if (!\is_string($name) || !\is_string($dsn) || false === ($tag['is_consumable'] ?? true)) {
                    continue;
                }
                $options = $container->findDefinition($id)->getArguments()[1] ?? [];
                $options = \is_array($options) ? $options : [];
                unset($options['transport_name']);
                $transports[$name] = ['dsn' => $dsn, 'options' => $options, 'failure' => true === ($tag['is_failure_transport'] ?? false)];
                $receivers[$name] = new Reference($id);
            }
        }

        $container->getDefinition(self::QUEUES)
            ->setArgument(0, ServiceLocatorTagPass::register($container, $receivers))
            ->setArgument(1, $transports)
            ->setArgument(2, $this->failureTransports($container))
        ;
    }

    /**
     * @return array<string, string>
     */
    private function failureTransports(ContainerBuilder $container): array
    {
        if (!$container->hasDefinition(self::FAILURE_LISTENER)) {
            return [];
        }

        $byName = $container->getDefinition(self::FAILURE_LISTENER)->getArguments()[2] ?? [];

        return \is_array($byName) ? array_filter($byName, static fn (mixed $failure, mixed $name): bool => \is_string($failure) && \is_string($name), \ARRAY_FILTER_USE_BOTH) : [];
    }
}
