<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink;

/**
 * The environment probes have no administration page to open: their action link is the documentation of the plugin
 * (an internal route that redirects to the README), so it never hard-codes a URL in a probe.
 */
final class DocumentationLink
{
    public const ROUTE = 'tmonier_platform_health_documentation';

    public static function create(): HealthActionLink
    {
        return new HealthActionLink(self::ROUTE);
    }
}
