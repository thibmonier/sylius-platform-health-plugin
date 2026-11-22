<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

/**
 * A probe. Every class implementing it is registered by autoconfiguration, without a line of configuration.
 *
 * The runner never lets a probe break the page (see HealthCheckRunner), but a probe stays read-only and
 * applies its own timeout to any network or database call: PHP cannot interrupt a probe that is running.
 */
interface HealthCheckInterface
{
    public const TAG = 'tmonier_sylius_platform_health.check';

    /**
     * Unique, dotted, constant: for example "messenger.failed_transport". It is read at container compilation,
     * on an instance built without its constructor, so it must not depend on any constructor argument.
     */
    public function identifier(): string;

    public function category(): HealthCategory;

    public function run(): HealthResult;
}
