<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Fixture;

use PHPUnit\Framework\TestCase;

/**
 * The composer.json of the monitored plugin, as it would be published: the health plugin is suggested and used by its tests, never required.
 */
final class DemoWebhooksComposerTest extends TestCase
{
    private const HEALTH_PLUGIN = 'tmonier/sylius-platform-health-plugin';

    public function testTheHealthPluginIsNeverRequired(): void
    {
        self::assertArrayNotHasKey(self::HEALTH_PLUGIN, $this->section('require'));
    }

    public function testTheHealthPluginIsSuggested(): void
    {
        self::assertArrayHasKey(self::HEALTH_PLUGIN, $this->section('suggest'));
    }

    public function testTheHealthPluginIsInstalledForTheTestsOfThePlugin(): void
    {
        self::assertArrayHasKey(self::HEALTH_PLUGIN, $this->section('require-dev'));
    }

    /**
     * @return array<mixed>
     */
    private function section(string $name): array
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../../Fixture/DemoWebhooks/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        $section = $composer[$name] ?? [];
        self::assertIsArray($section);

        return $section;
    }
}
