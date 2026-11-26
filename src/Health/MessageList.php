<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Several messages shown as one detail, each translated on its own: one line per declared command for the heartbeat.
 */
final readonly class MessageList implements TranslatableInterface
{
    /**
     * @param list<TranslatableInterface> $messages
     */
    public function __construct(
        public array $messages,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return implode(' · ', array_map(static fn (TranslatableInterface $message): string => $message->trans($translator, $locale), $this->messages));
    }
}
