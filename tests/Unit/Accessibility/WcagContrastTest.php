<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Accessibility;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\WcagContrast;

final class WcagContrastTest extends TestCase
{
    public function testBlackOnWhiteIsTwentyOneToOne(): void
    {
        self::assertEqualsWithDelta(21.0, WcagContrast::ratio('#000000', '#ffffff'), 0.001);
    }

    public function testTheRatioDoesNotDependOnTheOrderOfTheColours(): void
    {
        self::assertSame(WcagContrast::ratio('#767676', '#ffffff'), WcagContrast::ratio('#ffffff', '#767676'));
    }

    public function testTheKnownGreyOfTheWcagExamplesJustPassesAA(): void
    {
        self::assertEqualsWithDelta(4.54, WcagContrast::ratio('#767676', '#ffffff'), 0.01);
    }

    public function testAShortHexColourIsExpanded(): void
    {
        self::assertEqualsWithDelta(WcagContrast::ratio('#ffffff', '#000000'), WcagContrast::ratio('#fff', '#000'), 0.0001);
    }

    public function testAnythingButAHexColourIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WcagContrast::ratio('rebeccapurple', '#ffffff');
    }
}
