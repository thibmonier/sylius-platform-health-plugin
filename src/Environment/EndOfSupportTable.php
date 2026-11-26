<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

/**
 * End-of-support dates, frozen in the code: the administration never calls the network (no outgoing telemetry).
 *
 * The table is renewed with every release of the plugin. Twelve months after its version, the probes that rely
 * on it warn that it must be renewed, rather than answering a false "ok" from a forgotten table.
 */
final class EndOfSupportTable
{
    /** Year and month of the last review of the dates below. Every probe that reads the table displays it. */
    public const VERSION = '2026-12';

    private const MONTHS_OF_VALIDITY = 12;

    /** End of security support of each PHP branch (https://www.php.net/supported-versions.php). */
    private const PHP_SECURITY_END = [
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
        '8.5' => '2029-12-31',
        '8.6' => '2030-12-31',
    ];

    /**
     * End of security support of each Sylius minor branch (https://docs.sylius.com/public/open-source/release-cycle).
     * 2.2: March 2027, 2.3: December 2027, as published (reviewed on 2026-11-26 for v0.4.0).
     */
    private const SYLIUS_SUPPORT_END = [
        '2.2' => '2027-03-31',
        '2.3' => '2027-12-31',
    ];

    public function version(): string
    {
        return self::VERSION;
    }

    public function phpSecurityEnd(string $branch): ?\DateTimeImmutable
    {
        return $this->date(self::PHP_SECURITY_END[$branch] ?? null);
    }

    public function syliusSupportEnd(string $branch): ?\DateTimeImmutable
    {
        return $this->date(self::SYLIUS_SUPPORT_END[$branch] ?? null);
    }

    public function isStale(\DateTimeImmutable $now): bool
    {
        $renewal = $this->date(self::VERSION . '-01')?->modify(\sprintf('+%d months', self::MONTHS_OF_VALIDITY));

        return $renewal instanceof \DateTimeImmutable && $now > $renewal->setTime(23, 59, 59);
    }

    private function date(?string $date): ?\DateTimeImmutable
    {
        return null === $date ? null : new \DateTimeImmutable($date, new \DateTimeZone('UTC'));
    }
}
