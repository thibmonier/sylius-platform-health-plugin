<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class WritableDirsCheck implements HealthCheckInterface
{
    /**
     * @param array<string, string> $directories absolute path of each directory, keyed by the relative label shown
     */
    public function __construct(
        private array $directories,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.writable_dirs';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        $notWritable = [];
        foreach ($this->directories as $label => $path) {
            if (!$this->writable($path)) {
                $notWritable[] = $label;
            }
        }

        // The labels are relative ("var/log"): the absolute path of the server never reaches the screen.
        return [] === $notWritable
            ? HealthResult::ok(new TranslatableMessage('tmonier_platform_health.environment.writable_dirs.ok'))
            : HealthResult::critical(new TranslatableMessage('tmonier_platform_health.environment.writable_dirs.not_writable', ['%directories%' => implode(', ', $notWritable)]), null, DocumentationLink::create());
    }

    /**
     * A directory not created yet (the media directory before the first upload) is writable when its parent is.
     */
    private function writable(string $path): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        return !file_exists($path) && is_dir(\dirname($path)) && is_writable(\dirname($path));
    }
}
