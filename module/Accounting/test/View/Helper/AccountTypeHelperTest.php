<?php

declare(strict_types=1);

namespace AccountingTest\View\Helper;

use Accounting\ValueObject\AccountType;
use Accounting\View\Helper\AccountTypeHelper;
use PHPUnit\Framework\TestCase;

final class AccountTypeHelperTest extends TestCase
{
    private AccountTypeHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new AccountTypeHelper();
    }

    public function testInvokeReturnsSelfForMethodAccess(): void
    {
        $this->assertSame($this->helper, ($this->helper)());
    }

    /** @dataProvider typeProvider */
    public function testColourIsAHexValue(AccountType $type): void
    {
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $this->helper->colour($type));
    }

    /** @dataProvider headingProvider */
    public function testHeading(AccountType $type, string $expected): void
    {
        $this->assertSame($expected, $this->helper->heading($type));
    }

    public function testEveryTypeHasADistinctColour(): void
    {
        $colours = array_map(fn (AccountType $t) => $this->helper->colour($t), AccountType::cases());

        $this->assertSame($colours, array_unique($colours), 'Account type colours should be distinct');
    }

    public static function typeProvider(): array
    {
        return array_map(fn (AccountType $t) => [$t], AccountType::cases());
    }

    public static function headingProvider(): array
    {
        return [
            [AccountType::Asset, 'Assets'],
            [AccountType::Liability, 'Liabilities'],
            [AccountType::Equity, 'Equity'],
            [AccountType::Income, 'Income'],
            [AccountType::Expense, 'Expenses'],
        ];
    }
}
