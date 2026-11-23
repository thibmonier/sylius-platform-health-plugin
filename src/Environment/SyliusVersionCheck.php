<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class SyliusVersionCheck implements HealthCheckInterface
{
    public function __construct(
        private SupportAssessor $assessor,
        private EndOfSupportTable $table,
        private ?string $version = null,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.sylius_version';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        $version = $this->version ?? SyliusCoreBundle::VERSION;
        $branch = VersionBranch::of($version);
        if (null === $branch) {
            return $this->assessor->unreadable('Sylius');
        }

        return $this->assessor->assessTable('Sylius', $version, $this->table->syliusSupportEnd($branch));
    }
}
