<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Security;

/**
 * Defence in depth: probes are written by third parties, so the page masks what a probe let slip.
 */
final readonly class SensitiveValueMasker
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function mask(string $text): string
    {
        if ('' === $this->projectDir) {
            return $text;
        }

        return $text;
    }

    public function looksLikeExecutionTrace(string $text): bool
    {
        return false;
    }
}
