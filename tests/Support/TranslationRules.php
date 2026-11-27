<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

/**
 * The completeness and wording rules of the catalogues, shared by the unit test (the YAML files) and the integration
 * test (the catalogues the translator of the application really loaded). Each method returns the violations, none when
 * the rule holds.
 */
final class TranslationRules
{
    /**
     * Wording that was true of an earlier version of the plugin and is not any more.
     */
    private const STALE = ['ne fournit aucun', 'ships no'];

    /**
     * The vocabulary of the page and of the console is "check" / "contrôle"; "probe" and "sonde" name the concept in the
     * documentation only (decision of Thibaut, 2026-11-26).
     */
    private const VOCABULARY = ['en' => 'probe', 'fr' => 'sonde'];

    /**
     * @param array<string, string> $english
     * @param array<string, string> $french
     *
     * @return list<string>
     */
    public static function keysOnlyInOneLanguage(array $english, array $french): array
    {
        $only = [];
        foreach (array_diff_key($english, $french) as $key => $_) {
            $only[] = $key . ' (en only)';
        }
        foreach (array_diff_key($french, $english) as $key => $_) {
            $only[] = $key . ' (fr only)';
        }
        sort($only);

        return $only;
    }

    /**
     * @param array<string, string> $english
     * @param array<string, string> $french
     *
     * @return list<string>
     */
    public static function placeholderMismatches(array $english, array $french): array
    {
        $mismatches = [];
        foreach (array_intersect_key($english, $french) as $key => $text) {
            if (self::placeholders($text) !== self::placeholders($french[$key])) {
                $mismatches[] = $key;
            }
        }

        return $mismatches;
    }

    /**
     * @param array<string, string> $catalogue
     * @param list<string>          $referenced
     *
     * @return list<string>
     */
    public static function missingKeys(array $catalogue, array $referenced): array
    {
        return array_values(array_diff($referenced, array_keys($catalogue)));
    }

    /**
     * @param array<string, string> $catalogue
     * @param list<string>          $referenced
     *
     * @return list<string>
     */
    public static function unusedKeys(array $catalogue, array $referenced): array
    {
        return array_values(array_diff(array_keys($catalogue), $referenced));
    }

    /**
     * @param array<string, string> $catalogue
     *
     * @return list<string>
     */
    public static function wordingViolations(array $catalogue, string $locale): array
    {
        $violations = [];
        foreach ($catalogue as $key => $text) {
            foreach (self::problems($key, $text, $locale) as $problem) {
                $violations[] = $key . ': ' . $problem;
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function problems(string $key, string $text, string $locale): array
    {
        $problems = [];
        $lowered = mb_strtolower($text);
        foreach (self::STALE as $phrase) {
            if (str_contains($lowered, $phrase)) {
                $problems[] = sprintf('stale wording "%s"', $phrase);
            }
        }
        if (str_contains($lowered, self::VOCABULARY[$locale])) {
            $problems[] = sprintf('says "%s" where the page says "%s"', self::VOCABULARY[$locale], 'en' === $locale ? 'check' : 'contrôle');
        }
        if (str_contains($text, '...')) {
            $problems[] = 'three dots instead of the ellipsis character';
        }
        if ('' === trim($text)) {
            $problems[] = 'empty text';
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private static function placeholders(string $text): array
    {
        preg_match_all('/%[a-z_]+%/', $text, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
