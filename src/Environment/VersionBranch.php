<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

/**
 * "8.3.12", "v2.3.3" or "2.2.9-RC1" give the branch "8.3", "2.3", "2.2"; "dev-main" gives none.
 */
final class VersionBranch
{
    public static function of(string $version): ?string
    {
        return 1 === preg_match('/^v?(\d+\.\d+)(?:\.|$|-)/', $version, $matches) ? $matches[1] : null;
    }
}
