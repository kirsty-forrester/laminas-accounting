<?php

namespace Accounting\ValueObject;

enum AccountType: string
{
    case Asset     = 'asset';      // a "has it" bucket
    case Expense   = 'expense';    // a "used it up" bucket
    case Liability = 'liability';  // the lender's door
    case Equity    = 'equity';     // the owner's door
    case Income    = 'income';     // the earnings door

    /**
     * The side on which this account type carries a positive balance.
     * Assets and expenses are debit-normal; liabilities, equity and
     * income are credit-normal.
     */
    public function normalBalance(): Direction
    {
        return match ($this) {
            self::Asset, self::Expense               => Direction::Debit,
            self::Liability, self::Equity, self::Income => Direction::Credit,
        };
    }
}
