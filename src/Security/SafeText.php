<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Security;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The text of a probe as a human may read it, on the page as in the console: translated, then masked, never a raw execution trace.
 */
final readonly class SafeText
{
    public function __construct(
        private TranslatorInterface $translator,
        private SensitiveValueMasker $masker,
        private LoggerInterface $logger,
    ) {
    }

    public function render(TranslatableInterface $text, string $identifier): string
    {
        $raw = $text->trans($this->translator);

        if ($this->masker->looksLikeExecutionTrace($raw)) {
            // The probe is at fault, not the administrator: the log names it, and never carries the trace.
            $this->logger->warning('Health check "{identifier}" returned an execution trace: hidden from the output.', ['identifier' => $identifier]);

            return $this->translator->trans('tmonier_platform_health.result.detail_hidden');
        }

        return $this->masker->mask($raw);
    }
}
