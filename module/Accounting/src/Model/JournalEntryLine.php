<?php

namespace Accounting\Model;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;

class JournalEntryLine
{
    public function __construct(
        private ?int $journalEntryLineId,
        private ?int $journalEntryId,
        private int $accountId,
        private Direction $direction,
        private Money $amount,
    ) {}

    public function getJournalEntryLineId(): ?int
    {
        return $this->journalEntryLineId;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
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
}
