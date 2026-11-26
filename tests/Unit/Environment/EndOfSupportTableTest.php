<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusPlatformHealthPlugin\Environment\EndOfSupportTable;

final class EndOfSupportTableTest extends TestCase
{
    public function testTheTableVersionIs2026Dash12(): void
    {
        self::assertSame('2026-12', (new EndOfSupportTable())->version());
    }

    public function testTheDatesReviewedForVersionZeroFourAreThePublishedOnes(): void
    {
        $table = new EndOfSupportTable();

        self::assertSame('2030-12-31', $table->phpSecurityEnd('8.6')?->format('Y-m-d'));
        self::assertSame('2027-12-31', $table->syliusSupportEnd('2.3')?->format('Y-m-d'));
    }

    public function testEveryBranchTestedInTheCiIsInTheTable(): void
    {
        $table = new EndOfSupportTable();

        foreach (['8.2', '8.3', '8.4', '8.5'] as $branch) {
            self::assertInstanceOf(\DateTimeImmutable::class, $table->phpSecurityEnd($branch), 'PHP ' . $branch);
        }
        foreach (['2.2', '2.3'] as $branch) {
            self::assertInstanceOf(\DateTimeImmutable::class, $table->syliusSupportEnd($branch), 'Sylius ' . $branch);
        }
    }

    public function testAnUnknownBranchHasNoDate(): void
    {
        $table = new EndOfSupportTable();

        self::assertNull($table->phpSecurityEnd('9.9'));
        self::assertNull($table->syliusSupportEnd('9.9'));
    }

    public function testTheTableIsStaleOnlyMoreThanTwelveMonthsAfterItsVersion(): void
    {
        $table = new EndOfSupportTable();

        self::assertFalse($table->isStale(new \DateTimeImmutable('2027-12-01', new \DateTimeZone('UTC'))));
        self::assertTrue($table->isStale(new \DateTimeImmutable('2027-12-02', new \DateTimeZone('UTC'))));
    }
}
