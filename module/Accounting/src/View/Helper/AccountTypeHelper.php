<?php

namespace Accounting\View\Helper;

use Accounting\ValueObject\AccountType;
use Laminas\View\Helper\AbstractHelper;

/**
 * Presentation for account types (colours, grouping headings). Kept out of the
 * AccountType value object so the domain carries no UI concerns.
 *
 * Usage in templates: $this->accountType()->colour($type)
 */
final class AccountTypeHelper extends AbstractHelper
{
    public function __invoke(): self
    {
        return $this;
    }

    /** Accent colour (hex) used to theme a type's section. */
    public function colour(AccountType $type): string
    {
        return match ($type) {
            AccountType::Asset     => '#1f9cea', // aqua
            AccountType::Liability => '#f5533d', // coral
            AccountType::Equity    => '#8e5bd8', // violet
            AccountType::Income    => '#4e9e1f', // green
            AccountType::Expense   => '#f0932b', // amber
        };
    }

    /** Plural heading used when grouping accounts of a type. */
    public function heading(AccountType $type): string
    {
        return match ($type) {
            AccountType::Asset     => 'Assets',
            AccountType::Liability => 'Liabilities',
            AccountType::Equity    => 'Equity',
            AccountType::Income    => 'Income',
            AccountType::Expense   => 'Expenses',
        };
    }
}
