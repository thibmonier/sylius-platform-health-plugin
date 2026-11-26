<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\BrokenCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\OutboxCheck;

/*
 * The probes of the plugin, apart from its other services: the extension loads this file only when the health plugin
 * is installed. The autoconfiguration of the health plugin gives them their tag.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('demo_webhooks.health.outbox', OutboxCheck::class)->autoconfigure();
    $services->set('demo_webhooks.health.broken', BrokenCheck::class)->autoconfigure();
};
