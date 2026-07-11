<?php

namespace Accounting\Model;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;

class JournalEntryLine
{
    /** The owning side of the association back to the parent entry. */
    private ?JournalEntry $journalEntry = null;

    public function __construct(
        private ?int $journalEntryLineId,
        private int $accountId,
        private Direction $direction,
        private Money $amount,
    ) {}

    /** Called by JournalEntry::addLine() to set the back-reference. */
    public function assignToEntry(JournalEntry $entry): void
    {
        $this->journalEntry = $entry;
    }

    public function getJournalEntryLineId(): ?int
    {
        return $this->journalEntryLineId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function signedAgainst(Direction $normalBalance): Money
    {
        return $this->direction === $normalBalance
            ? $this->amount
            : $this->amount->negate();
    }
}
