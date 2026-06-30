<?php

namespace Accounting\Exceptions;

use Accounting\ValueObject\JournalEntryStatus;
use DomainException;

final class IllegalTransitionException extends DomainException
{
    public function __construct(JournalEntryStatus $from, JournalEntryStatus $to)
    {
        parent::__construct(sprintf(
            'Cannot transition journal entry from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}