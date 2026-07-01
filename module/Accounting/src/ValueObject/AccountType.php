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

    /** Plural heading used when grouping accounts of this type. */
    public function heading(): string
    {
        return match ($this) {
            self::Asset     => 'Assets',
            self::Liability => 'Liabilities',
            self::Equity    => 'Equity',
            self::Income    => 'Income',
            self::Expense   => 'Expenses',
        };
    }

    /** Accent colour (hex) used to theme this type's section in the UI. */
    public function colour(): string
    {
        return match ($this) {
            self::Asset     => '#1f9cea', // aqua
            self::Liability => '#f5533d', // coral
            self::Equity    => '#8e5bd8', // violet
            self::Income    => '#4e9e1f', // green
            self::Expense   => '#f0932b', // amber
        };
    }
}
