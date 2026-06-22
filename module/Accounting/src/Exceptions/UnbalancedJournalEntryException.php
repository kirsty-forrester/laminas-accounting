<?php

namespace Accounting\Exceptions;

use Accounting\Model\Money;
use DomainException;

final class UnbalancedJournalEntryException extends DomainException
{
    public function __construct(Money $debits, Money $credits)
    {
        parent::__construct(sprintf(
            'Journal entry does not balance: debits %s, credits %s',
            $debits->format(),
            $credits->format(),
        ));
    }
}