<?php

declare(strict_types=1);

namespace AccountingTest\ValueObject;

use Accounting\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testFromMinorStoresPennies(): void
    {
        $this->assertSame(1234, Money::fromMinor(1234)->pennies);
    }

    public function testZeroIsZeroPennies(): void
    {
        $this->assertSame(0, Money::zero()->pennies);
    }

    /**
     * @dataProvider decimalProvider
     */
    public function testFromDecimalParsesToPennies(string $pounds, int $expected): void
    {
        $this->assertSame($expected, Money::fromDecimal($pounds)->pennies);
    }

    public static function decimalProvider(): array
    {
        return [
            'whole pounds'   => ['500.00', 50000],
            'pounds + pence' => ['12.34', 1234],
            'single decimal' => ['0.1', 10],
            'negative'       => ['-5.00', -500],
        ];
    }

    /**
     * A third decimal place is rounded half-up, not truncated.
     *
     * @dataProvider roundingProvider
     */
    public function testFromDecimalRoundsThirdDecimal(string $pounds, int $expected): void
    {
        $this->assertSame($expected, Money::fromDecimal($pounds)->pennies);
    }

    public static function roundingProvider(): array
    {
        return [
            'rounds up'          => ['12.349', 1235],
            'rounds down'        => ['12.344', 1234],
            'classic 2.675'      => ['2.675', 268],
            'half rounds up'     => ['1.005', 101],
            'carries into penny' => ['0.999', 100],
            'carries into pound' => ['9.999', 1000],
            'negative rounds'    => ['-12.349', -1235],
        ];
    }

    public function testAdd(): void
    {
        $sum = Money::fromMinor(1000)->add(Money::fromMinor(250));
        $this->assertSame(1250, $sum->pennies);
    }

    public function testSubtractCanGoNegative(): void
    {
        $result = Money::fromMinor(100)->subtract(Money::fromMinor(250));
        $this->assertSame(-150, $result->pennies);
    }

    public function testEquals(): void
    {
        $this->assertTrue(Money::fromMinor(999)->equals(Money::fromMinor(999)));
        $this->assertFalse(Money::fromMinor(999)->equals(Money::fromMinor(1000)));
    }

    public function testArithmeticReturnsNewInstances(): void
    {
        $original = Money::fromMinor(1000);
        $original->add(Money::fromMinor(500));

        // Money is immutable: the original is unchanged by add().
        $this->assertSame(1000, $original->pennies);
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFormat(int $pennies, string $expected): void
    {
        $this->assertSame($expected, Money::fromMinor($pennies)->format());
    }

    public static function formatProvider(): array
    {
        return [
            'pounds'        => [50000, '£500.00'],
            'sub-pound'     => [5, '£0.05'],
            'mixed'         => [1234, '£12.34'],
            'negative'      => [-500, '-£5.00'],
            'zero'          => [0, '£0.00'],
        ];
    }
}
