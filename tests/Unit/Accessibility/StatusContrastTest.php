<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Accessibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\DesignTokens;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\WcagContrast;

/**
 * WCAG 2.2 AA, measured and not estimated (US-291): 1.4.3 asks 4.5:1 for text, 1.4.11 asks 3:1 for the icons and
 * the borders that carry the meaning of a status, in the light and in the dark theme of the administration.
 */
final class StatusContrastTest extends TestCase
{
    private const TEXT = 4.5;

    private const NON_TEXT = 3.0;

    private const STATUSES = ['ok', 'warning', 'critical', 'unknown'];

    /**
     * @return iterable<string, array{string, string, string, float}>
     */
    public static function pairs(): iterable
    {
        foreach ([DesignTokens::LIGHT, DesignTokens::DARK] as $theme) {
            yield $theme . ': text on the surface' => [$theme, '--tph-text', '--tph-surface', self::TEXT];
            yield $theme . ': muted text on the surface' => [$theme, '--tph-muted', '--tph-surface', self::TEXT];
            yield $theme . ': link on the surface' => [$theme, '--tph-link', '--tph-surface', self::TEXT];
            foreach (self::STATUSES as $status) {
                yield sprintf('%s: %s label on its badge', $theme, $status) => [$theme, sprintf('--tph-%s-text', $status), sprintf('--tph-%s-bg', $status), self::TEXT];
                yield sprintf('%s: %s icon on its badge', $theme, $status) => [$theme, sprintf('--tph-%s-icon', $status), sprintf('--tph-%s-bg', $status), self::NON_TEXT];
                yield sprintf('%s: %s border on the surface', $theme, $status) => [$theme, sprintf('--tph-%s-border', $status), '--tph-surface', self::NON_TEXT];
            }
        }
    }

    #[DataProvider('pairs')]
    public function testThePairOfTokensReachesItsMinimumRatio(string $theme, string $foreground, string $background, float $minimum): void
    {
        $tokens = DesignTokens::read()[$theme];
        self::assertArrayHasKey($foreground, $tokens, sprintf('%s is not defined in the %s theme.', $foreground, $theme));
        self::assertArrayHasKey($background, $tokens, sprintf('%s is not defined in the %s theme.', $background, $theme));

        $ratio = WcagContrast::ratio($tokens[$foreground], $tokens[$background]);

        self::assertGreaterThanOrEqual(
            $minimum,
            round($ratio, 2),
            sprintf('%s (%s) on %s (%s) in the %s theme is %.2f:1, %.1f:1 required.', $foreground, $tokens[$foreground], $background, $tokens[$background], $theme, $ratio, $minimum),
        );
    }

    public function testBothThemesDefineTheSameTokens(): void
    {
        $themes = DesignTokens::read();

        self::assertNotSame([], $themes[DesignTokens::LIGHT]);
        self::assertSame(array_keys($themes[DesignTokens::LIGHT]), array_keys($themes[DesignTokens::DARK]));
    }

    public function testNoTemplateOfThePageCarriesAColourOfItsOwn(): void
    {
        $offenders = [];
        foreach ([...glob(__DIR__ . '/../../../templates/admin/*.html.twig') ?: [], ...glob(__DIR__ . '/../../../templates/admin/index/*.html.twig') ?: []] as $template) {
            if (str_ends_with($template, '_tokens.html.twig')) {
                continue;
            }
            if (1 === preg_match('/#[0-9a-fA-F]{3,6}\b|style="|\b(?:bg|text)-(?:success|danger|warning|light|dark|white)\b/', (string) file_get_contents($template), $match)) {
                $offenders[] = basename($template) . ' (' . $match[0] . ')';
            }
        }

        self::assertSame([], $offenders, 'Colours belong to the tokens, not to the templates.');
    }
}
