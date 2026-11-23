<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Twig;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Security\SensitiveValueMasker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The text of a probe as the page shows it: translated, then masked, never a raw execution trace.
 */
final class SafeTextExtension extends AbstractExtension
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SensitiveValueMasker $masker,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('tmonier_platform_health_text', $this->text(...))];
    }

    public function text(TranslatableInterface $text, string $identifier): string
    {
        $raw = $text->trans($this->translator);

        if ($this->masker->looksLikeExecutionTrace($raw)) {
            $this->logger->notice('Stub.');
        }

        return $raw;
    }
}
