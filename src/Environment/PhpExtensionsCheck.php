<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class PhpExtensionsCheck implements HealthCheckInterface
{
    private const REQUIRED = ['intl', 'mbstring', 'ctype', 'iconv', 'xml', 'curl', 'pdo'];

    /** One of these is enough: Sylius generates its thumbnails with either. */
    private const IMAGE = ['gd', 'imagick'];

    private \Closure $isLoaded;

    /**
     * @param (\Closure(string): bool)|null $isLoaded
     */
    public function __construct(?\Closure $isLoaded = null)
    {
        $this->isLoaded = $isLoaded ?? \extension_loaded(...);
    }

    public function identifier(): string
    {
        return 'environment.php_extensions';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        $missing = array_values(array_filter(self::REQUIRED, fn (string $extension): bool => !($this->isLoaded)($extension)));
        if ([] === array_filter(self::IMAGE, fn (string $extension): bool => ($this->isLoaded)($extension))) {
            $missing[] = implode('|', self::IMAGE);
        }

        if ([] === $missing) {
            return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.environment.extensions.ok'));
        }

        return HealthResult::critical(
            new TranslatableMessage('tmonier_platform_health.environment.extensions.missing'),
            new TranslatableMessage('tmonier_platform_health.environment.extensions.detail', ['%missing%' => implode(', ', $missing)]),
            DocumentationLink::create(),
        );
    }
}
