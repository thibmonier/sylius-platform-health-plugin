<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

/**
 * WCAG 2.2 contrast ratio between two sRGB colours (https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio).
 */
final class WcagContrast
{
    public static function ratio(string $foreground, string $background): float
    {
        $light = max(self::luminance($foreground), self::luminance($background));
        $dark = min(self::luminance($foreground), self::luminance($background));

        return ($light + 0.05) / ($dark + 0.05);
    }

    private static function luminance(string $hex): float
    {
        [$red, $green, $blue] = array_map(self::linear(...), self::channels($hex));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    private static function linear(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }

    /**
     * @return list<float>
     */
    private static function channels(string $hex): array
    {
        $digits = ltrim($hex, '#');
        if (3 === \strlen($digits)) {
            $digits = $digits[0] . $digits[0] . $digits[1] . $digits[1] . $digits[2] . $digits[2];
        }
        if (1 !== preg_match('/^[0-9a-fA-F]{6}$/', $digits)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a #rgb or #rrggbb colour.', $hex));
        }

        return array_map(static fn (string $pair): float => hexdec($pair) / 255, str_split($digits, 2));
    }
}
