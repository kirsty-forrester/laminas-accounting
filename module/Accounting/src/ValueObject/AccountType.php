<?php

namespace Accounting\ValueObject;

enum AccountType: string
{
    case Asset     = 'asset';      // a "has it" bucket
    case Expense   = 'expense';    // a "used it up" bucket
    case Liability = 'liability';  // the lender's door
    case Equity    = 'equity';     // the owner's door
    case Income    = 'income';     // the earnings door
}