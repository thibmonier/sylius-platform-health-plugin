<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

/**
 * Two probes with the same identifier are a configuration error, caught when the container is compiled
 * (cache:warmup, so in CI), not when the page is displayed.
 */
final class UniqueIdentifierPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var array<string, string> $classByIdentifier */
        $classByIdentifier = [];
        foreach (array_keys($container->findTaggedServiceIds(HealthCheckInterface::TAG)) as $serviceId) {
            $class = $this->classOf($container, $serviceId);
            $identifier = $this->identifierOf($class);

            if (isset($classByIdentifier[$identifier])) {
                throw new \LogicException(\sprintf('The health checks "%s" and "%s" declare the same identifier "%s": an identifier must be unique.', $classByIdentifier[$identifier], $class, $identifier));
            }

            $classByIdentifier[$identifier] = $class;
        }
    }

    private function classOf(ContainerBuilder $container, string $serviceId): string
    {
        $class = $container->getParameterBag()->resolveValue($container->getDefinition($serviceId)->getClass() ?? $serviceId);

        return \is_string($class) ? $class : $serviceId;
    }

    private function identifierOf(string $class): string
    {
        try {
            if (!class_exists($class)) {
                throw new \LogicException('The class does not exist.');
            }

            $check = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
            if (!$check instanceof HealthCheckInterface) {
                throw new \LogicException(\sprintf('The class does not implement "%s".', HealthCheckInterface::class));
            }

            return $check->identifier();
        } catch (\Throwable $previous) {
            throw new \LogicException(\sprintf('Cannot read the identifier of the health check "%s": identifier() must return a constant and must not depend on the constructor (%s).', $class, $previous->getMessage()), 0, $previous);
        }
    }
}
