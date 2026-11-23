<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Translation;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\TranslationCatalogue;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\TranslationRules;

/**
 * The two YAML catalogues of the plugin, read as files (US-292).
 */
final class CatalogueFilesTest extends TestCase
{
    public function testTheEnglishAndFrenchCataloguesDefineExactlyTheSameKeys(): void
    {
        self::assertSame([], TranslationRules::keysOnlyInOneLanguage(TranslationCatalogue::fromFile('en'), TranslationCatalogue::fromFile('fr')));
    }

    public function testTheTwoLanguagesUseTheSamePlaceholders(): void
    {
        self::assertSame([], TranslationRules::placeholderMismatches(TranslationCatalogue::fromFile('en'), TranslationCatalogue::fromFile('fr')));
    }

    /**
     * @dataProvider locales
     */
    public function testNoKeyAskedForByTheCodeOrTheTemplatesIsMissing(string $locale): void
    {
        self::assertSame([], TranslationRules::missingKeys(TranslationCatalogue::fromFile($locale), TranslationCatalogue::referencedKeys()));
    }

    /**
     * @dataProvider locales
     */
    public function testNoKeyOfTheCatalogueIsLeftUnused(string $locale): void
    {
        self::assertSame([], TranslationRules::unusedKeys(TranslationCatalogue::fromFile($locale), TranslationCatalogue::referencedKeys()));
    }

    /**
     * @dataProvider locales
     */
    public function testTheWordingIsCurrentAndConsistent(string $locale): void
    {
        self::assertSame([], TranslationRules::wordingViolations(TranslationCatalogue::fromFile($locale), $locale));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function locales(): iterable
    {
        yield 'en' => ['en'];
        yield 'fr' => ['fr'];
    }
}
