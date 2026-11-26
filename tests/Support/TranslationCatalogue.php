<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Symfony\Component\Yaml\Yaml;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * The translation keys of the plugin: what the catalogues define and what the code and the templates reference.
 */
final class TranslationCatalogue
{
    public const ROOT = 'tmonier_platform_health';

    private const REFERENCE = '/' . self::ROOT . '\.(?:result|environment|data|scheduled_tasks|status|category|console|ui)(?:\.[a-z_]+)*\.?/';

    private const SUPPORT_PREFIX = self::ROOT . '.environment.support.';

    /**
     * @return array<string, string> the catalogue of a locale, flattened to "tmonier_platform_health.ui.title" => text
     */
    public static function fromFile(string $locale): array
    {
        $parsed = Yaml::parseFile(self::packageRoot() . '/translations/messages.' . $locale . '.yaml');
        \assert(\is_array($parsed));

        return self::flatten($parsed, '');
    }

    /**
     * @param array<array-key, mixed> $tree
     *
     * @return array<string, string>
     */
    public static function flatten(array $tree, string $prefix): array
    {
        $flat = [];
        foreach ($tree as $key => $value) {
            $path = '' === $prefix ? (string) $key : $prefix . '.' . $key;
            if (\is_array($value)) {
                $flat += self::flatten($value, $path);

                continue;
            }
            \assert(\is_string($value));
            $flat[$path] = $value;
        }

        return $flat;
    }

    /**
     * @return list<string> the keys the code and the templates ask for, the dynamic ones (category, status, support) expanded
     */
    public static function referencedKeys(): array
    {
        $keys = [];
        foreach (self::sources() as $path) {
            $content = file_get_contents($path);
            \assert(\is_string($content));
            preg_match_all(self::REFERENCE, $content, $matches);
            foreach ($matches[0] as $reference) {
                array_push($keys, ...self::expand($reference, $content));
            }
        }

        $keys = array_values(array_unique($keys));
        sort($keys);

        return $keys;
    }

    /**
     * @return list<string>
     */
    private static function expand(string $reference, string $source): array
    {
        if (!str_ends_with($reference, '.')) {
            return [$reference];
        }

        return match ($reference) {
            self::ROOT . '.category.' => array_map(static fn (HealthCategory $case): string => $reference . $case->value, HealthCategory::cases()),
            self::ROOT . '.status.' => array_map(static fn (HealthStatus $case): string => $reference . $case->value, HealthStatus::cases()),
            self::SUPPORT_PREFIX => self::supportKeys($source),
            default => throw new \LogicException(sprintf('The dynamic translation key "%s" is not known to the completeness test.', $reference)),
        };
    }

    /**
     * @return list<string>
     */
    private static function supportKeys(string $source): array
    {
        preg_match_all('/self::KEY \. \'([a-z_.]+)\'/', $source, $matches);

        return array_map(static fn (string $suffix): string => self::SUPPORT_PREFIX . $suffix, $matches[1]);
    }

    /**
     * @return list<string>
     */
    private static function sources(): array
    {
        $files = [];
        foreach (['src', 'templates', 'config'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::packageRoot() . '/' . $directory, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                \assert($file instanceof \SplFileInfo);
                if (\in_array($file->getExtension(), ['php', 'twig', 'yaml'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private static function packageRoot(): string
    {
        return \dirname(__DIR__, 2);
    }
}
