<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

/**
 * The rhythm declared for a command, written "30s", "15m", "2h" or "1d".
 */
final class BeatInterval
{
    private const UNITS = ['s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400];

    /**
     * Null when the text is not a positive duration.
     */
    public static function seconds(mixed $text): ?int
    {
        if (!\is_string($text) || 1 !== preg_match('/^([1-9]\d{0,5})\s*([smhd])$/', trim($text), $match)) {
            return null;
        }

        return (int) $match[1] * self::UNITS[$match[2]];
    }
}
