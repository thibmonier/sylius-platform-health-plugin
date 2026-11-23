<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class PhpVersionCheck implements HealthCheckInterface
{
    public function __construct(
        private SupportAssessor $assessor,
        private EndOfSupportTable $table,
        private ?string $version = null,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.php_version';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        $version = $this->version ?? \PHP_VERSION;
        $branch = VersionBranch::of($version);
        if (null === $branch) {
            return $this->assessor->unreadable('PHP');
        }

        return $this->assessor->assessTable('PHP', $version, $this->table->phpSecurityEnd($branch));
    }
}
