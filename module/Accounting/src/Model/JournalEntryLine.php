<?php

namespace Accounting\Model;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;

final class JournalEntryLine
{
    private ?int $journalEntryLineId = null;
    private int $journalEntryId;
    private int $accountId;
    private Direction $direction;
    private Money $amount;

    public function getJournalEntryLineId(): ?int
    {
        return $this->journalEntryLineId;
    }

    public function setJournalEntryLineId(int $journalEntryLineId): void
    {
        $this->journalEntryLineId = $journalEntryLineId;
    }

    public function getJournalEntryId(): int
    {
        return $this->journalEntryId;
    }

    public function setJournalEntryId(int $journalEntryId): void
    {
        $this->journalEntryId = $journalEntryId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(Direction $direction): void
    {
        $this->direction = $direction;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount;
    }
}
