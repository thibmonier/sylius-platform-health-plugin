<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use PHPUnit\Framework\Assert;

/**
 * The JSON document of the console command, decoded without throwing: an output that is not JSON is a failed assertion.
 */
trait ReadsJsonOutput
{
    /**
     * @return array<string, mixed>
     */
    private static function document(string $output): array
    {
        $document = json_decode($output, true);
        Assert::assertIsArray($document, 'The output should be a JSON object, got: ' . $output);

        $typed = [];
        foreach ($document as $key => $value) {
            $typed[(string) $key] = $value;
        }

        return $typed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function checks(string $output): array
    {
        $checks = self::document($output)['checks'] ?? null;
        Assert::assertIsArray($checks, 'The document should carry a "checks" list.');

        $typed = [];
        foreach ($checks as $check) {
            Assert::assertIsArray($check);
            $line = [];
            foreach ($check as $key => $value) {
                $line[(string) $key] = $value;
            }
            $typed[] = $line;
        }

        return $typed;
    }

    /**
     * @return list<string>
     */
    private static function identifiers(string $output): array
    {
        $identifiers = [];
        foreach (self::checks($output) as $check) {
            Assert::assertIsString($check['identifier'] ?? null);
            $identifiers[] = $check['identifier'];
        }

        return $identifiers;
    }
}
