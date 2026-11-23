<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Translation;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\TranslatorBagInterface;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\TranslationCatalogue;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\TranslationRules;

/**
 * The catalogues as the translator of the Sylius test application loads them (the plugin's, merged with the
 * application's): what an administrator really gets, not only what the YAML files say (US-292).
 */
final class LoadedCatalogueTest extends KernelTestCase
{
    public function testTheTranslatorKnowsEveryKeyTheCodeAndTheTemplatesAskForInBothLanguages(): void
    {
        foreach (['en', 'fr'] as $locale) {
            self::assertSame([], TranslationRules::missingKeys($this->loaded($locale), TranslationCatalogue::referencedKeys()), $locale);
        }
    }

    public function testTheLoadedCataloguesDefineTheSameKeysInBothLanguages(): void
    {
        self::assertSame([], TranslationRules::keysOnlyInOneLanguage($this->loaded('en'), $this->loaded('fr')));
    }

    public function testTheLoadedWordingIsCurrentAndConsistent(): void
    {
        foreach (['en', 'fr'] as $locale) {
            self::assertSame([], TranslationRules::wordingViolations($this->loaded($locale), $locale), $locale);
        }
    }

    /**
     * @return array<string, string> the plugin's keys of a locale, as loaded
     */
    private function loaded(string $locale): array
    {
        self::bootKernel();
        $translator = self::getContainer()->get('translator');
        self::assertInstanceOf(TranslatorBagInterface::class, $translator);

        $messages = $translator->getCatalogue($locale)->all('messages');
        $own = array_filter($messages, static fn (string $key): bool => str_starts_with($key, TranslationCatalogue::ROOT . '.'), \ARRAY_FILTER_USE_KEY);

        return array_map(static fn (mixed $text): string => \is_string($text) ? $text : '', $own);
    }
}
