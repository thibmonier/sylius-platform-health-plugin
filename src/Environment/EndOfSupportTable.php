<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

/**
 * Red stub (US-293): the dates arrive with the implementation.
 */
final class EndOfSupportTable
{
    public const VERSION = '';

    /** @var array<string, \DateTimeImmutable> */
    private array $dates = [];

    public function version(): string
    {
        return self::VERSION;
    }

    public function phpSecurityEnd(string $branch): ?\DateTimeImmutable
    {
        return $this->dates['php ' . $branch] ?? null;
    }

    public function syliusSupportEnd(string $branch): ?\DateTimeImmutable
    {
        return $this->dates['sylius ' . $branch] ?? null;
    }

    public function isStale(\DateTimeImmutable $now): bool
    {
        return false;
    }
}
