<?php

declare(strict_types=1);

namespace AccountingTest\ValueObject;

use Accounting\ValueObject\AccountType;
use Accounting\ValueObject\Direction;
use PHPUnit\Framework\TestCase;

final class AccountTypeTest extends TestCase
{
    /**
     * @dataProvider normalBalanceProvider
     */
    public function testNormalBalance(AccountType $type, Direction $expected): void
    {
        $this->assertSame($expected, $type->normalBalance());
    }

    public static function normalBalanceProvider(): array
    {
        return [
            'asset is debit-normal'      => [AccountType::Asset, Direction::Debit],
            'expense is debit-normal'    => [AccountType::Expense, Direction::Debit],
            'liability is credit-normal' => [AccountType::Liability, Direction::Credit],
            'equity is credit-normal'    => [AccountType::Equity, Direction::Credit],
            'income is credit-normal'    => [AccountType::Income, Direction::Credit],
        ];
    }
}
