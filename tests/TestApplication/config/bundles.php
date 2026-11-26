<?php

declare(strict_types=1);

use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DemoWebhooksBundle;
use Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin;

return [
    TmonierSyliusPlatformHealthPlugin::class => ['all' => true],
    // US-303: a plugin that provides a probe without requiring the health plugin.
    DemoWebhooksBundle::class => ['all' => true],
];
