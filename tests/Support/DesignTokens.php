<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

/**
 * The colour tokens of the page, read from the stylesheet partial that ships them: one block for the light theme
 * (:root and [data-bs-theme="light"]), one for the dark theme ([data-bs-theme="dark"]) that Tabler, and so the
 * administration of Sylius 2, switches on.
 */
final class DesignTokens
{
    public const LIGHT = 'light';

    public const DARK = 'dark';

    public const TEMPLATE = __DIR__ . '/../../templates/admin/_tokens.html.twig';

    /**
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public static function read(string $template = self::TEMPLATE): array
    {
        $css = (string) file_get_contents($template);
        $themes = [self::LIGHT => [], self::DARK => []];

        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $blocks, \PREG_SET_ORDER);
        foreach ($blocks as [, $selector, $body]) {
            $theme = str_contains($selector, 'data-bs-theme="dark"') ? self::DARK : self::LIGHT;
            preg_match_all('/(--tph-[a-z-]+)\s*:\s*(#[0-9a-fA-F]{3,6})\s*;/', $body, $declarations, \PREG_SET_ORDER);
            foreach ($declarations as [, $name, $value]) {
                $themes[$theme][$name] = $value;
            }
        }

        return $themes;
    }
}
