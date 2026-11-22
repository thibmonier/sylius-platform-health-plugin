<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * A probe whose behaviour the test hands over as a closure. Not for the container (see tests/Support/Fixture).
 */
final readonly class FakeCheck implements HealthCheckInterface
{
    /**
     * @param \Closure(): HealthResult $run
     */
    public function __construct(
        private string $identifier,
        private HealthCategory $category,
        private \Closure $run,
    ) {
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function category(): HealthCategory
    {
        return $this->category;
    }

    public function run(): HealthResult
    {
        return ($this->run)();
    }
}
