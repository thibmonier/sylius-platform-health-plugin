<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * Turns an end-of-support date into a result: critical once the date has passed, warning within the threshold,
 * ok otherwise. The date is the last supported day (the day after, it is critical). The clock is injected.
 */
final readonly class SupportAssessor
{
    private const KEY = 'tmonier_platform_health.environment.support.';

    public function __construct(
        private ClockInterface $clock,
        private EndOfSupportTable $table,
        private int $warningDays = 180,
    ) {
    }

    /**
     * For a software whose dates come from the table: an absent branch is unknown, never ok.
     */
    public function assessTable(string $name, string $version, ?\DateTimeImmutable $end): HealthResult
    {
        $detail = new TranslatableMessage(self::KEY . 'detail.table', ['%table%' => $this->table->version()]);
        if (!$end instanceof \DateTimeImmutable) {
            return HealthResult::unknown(new TranslatableMessage(self::KEY . 'branch_missing', ['%name%' => $name, '%version%' => $version]), $detail, $this->documentation());
        }

        $result = $this->assess($name, $version, $end, $detail);
        if (HealthStatus::Ok === $result->status && $this->table->isStale($this->clock->now())) {
            return HealthResult::warning(new TranslatableMessage(self::KEY . 'table_stale', ['%name%' => $name, '%version%' => $version, '%table%' => $this->table->version()]), $detail, $this->documentation());
        }

        return $result;
    }

    /**
     * For Symfony, whose own constants make the dates authoritative: an ended maintenance is a warning while the
     * security fixes last, the end of life is critical.
     */
    public function assessKernel(string $name, string $version, \DateTimeImmutable $maintenanceEnd, \DateTimeImmutable $lifeEnd): HealthResult
    {
        $detail = new TranslatableMessage(self::KEY . 'detail.kernel');
        $result = $this->assess($name, $version, $lifeEnd, $detail);
        if (HealthStatus::Ok !== $result->status || $this->daysUntil($maintenanceEnd) >= 0) {
            return $result;
        }

        return HealthResult::warning(new TranslatableMessage(self::KEY . 'maintenance_ended', $this->parameters($name, $version, $lifeEnd)), $detail, $this->documentation());
    }

    public function unreadable(string $name, ?TranslatableMessage $detail = null): HealthResult
    {
        return HealthResult::unknown(new TranslatableMessage(self::KEY . 'unreadable', ['%name%' => $name]), $detail, $this->documentation());
    }

    private function assess(string $name, string $version, \DateTimeImmutable $end, TranslatableMessage $detail): HealthResult
    {
        $days = $this->daysUntil($end);
        $parameters = $this->parameters($name, $version, $end);

        if ($days < 0) {
            return HealthResult::critical(new TranslatableMessage(self::KEY . 'critical', $parameters), $detail, $this->documentation());
        }

        if ($days <= $this->warningDays) {
            return HealthResult::warning(new TranslatableMessage(self::KEY . 'warning', $parameters), $detail, $this->documentation());
        }

        return HealthResult::ok(new TranslatableMessage(self::KEY . 'ok', $parameters), $detail);
    }

    /**
     * @return array<string, scalar>
     */
    private function parameters(string $name, string $version, \DateTimeImmutable $end): array
    {
        return ['%name%' => $name, '%version%' => $version, '%date%' => $end->format('Y-m-d'), '%days%' => $this->daysUntil($end)];
    }

    private function daysUntil(\DateTimeImmutable $end): int
    {
        $today = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->setTime(0, 0);

        return (int) $today->diff($end->setTime(0, 0))->format('%r%a');
    }

    private function documentation(): \Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink
    {
        return DocumentationLink::create();
    }
}
