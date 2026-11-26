<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Twig;

use Symfony\Contracts\Translation\TranslatableInterface;
use Tmonier\SyliusPlatformHealthPlugin\Security\SafeText;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The text of a probe as the page shows it: translated, then masked, never a raw execution trace.
 */
final class SafeTextExtension extends AbstractExtension
{
    public function __construct(
        private readonly SafeText $safeText,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('tmonier_platform_health_text', $this->text(...))];
    }

    public function text(TranslatableInterface $text, string $identifier): string
    {
        return $this->safeText->render($text, $identifier);
    }
}
