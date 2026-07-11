<?php

declare(strict_types=1);

namespace AccountingTest\Persistence\Type;

// SqlitePlatform vs SQLitePlatform - check version
use Accounting\Persistence\Type\MoneyType;
use Accounting\ValueObject\Money;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\TestCase;

class MoneyTypeTest extends TestCase
{
    private MoneyType $type;
    private SqlitePlatform $platform;

    protected function setUp(): void
    {
        $this->type     = new MoneyType();
        $this->platform = new SqlitePlatform();
    }

    public function testConvertsMoneyToPennies(): void
    {
        $this->assertSame(
            50000,
            $this->type->convertToDatabaseValue(Money::fromMinor(50000), $this->platform),
        );
    }

    public function testConvertsPenniesToMoney(): void
    {
        $money = $this->type->convertToPHPValue(50000, $this->platform);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertTrue($money->equals(Money::fromMinor(50000)));
    }

    public function testNullRoundTrips(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }
}
