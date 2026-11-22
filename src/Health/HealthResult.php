<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * What a probe answers: a status, a one-line translatable summary written for the administrator, an optional
 * detail and an optional action link. It never carries a trace, a raw exception message or a secret.
 */
final readonly class HealthResult
{
    public function __construct(
        public HealthStatus $status,
        public TranslatableInterface $summary,
        public ?TranslatableInterface $detail = null,
        public ?HealthActionLink $action = null,
    ) {
    }

    public static function ok(TranslatableInterface $summary, ?TranslatableInterface $detail = null, ?HealthActionLink $action = null): self
    {
        return new self(HealthStatus::Ok, $summary, $detail, $action);
    }

    public static function warning(TranslatableInterface $summary, ?TranslatableInterface $detail = null, ?HealthActionLink $action = null): self
    {
        return new self(HealthStatus::Warning, $summary, $detail, $action);
    }

    public static function critical(TranslatableInterface $summary, ?TranslatableInterface $detail = null, ?HealthActionLink $action = null): self
    {
        return new self(HealthStatus::Critical, $summary, $detail, $action);
    }

    public static function unknown(TranslatableInterface $summary, ?TranslatableInterface $detail = null, ?HealthActionLink $action = null): self
    {
        return new self(HealthStatus::Unknown, $summary, $detail, $action);
    }
}
