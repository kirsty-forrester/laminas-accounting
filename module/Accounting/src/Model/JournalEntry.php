<?php

namespace Accounting\Model;

use Accounting\Exceptions\UnbalancedJournalEntryException;
use DateTimeImmutable;

final class JournalEntry
{
    private ?int $journalEntryId = null;
    private DateTimeImmutable $date;
    private string $description;
    /** @var JournalEntryLine[] */
    private array $lines = [];

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
    }

    public function setJournalEntryId(int $journalEntryId): void
    {
        $this->journalEntryId = $journalEntryId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /** @return JournalEntryLine[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    /** @param JournalEntryLine[] $lines */
    public function setLines(array $lines): void
    {
        $debits  = Money::zero();
        $credits = Money::zero();

        foreach ($lines as $line) {
            if ($line->getDirection() === Direction::Debit) {
                $debits = $debits->add($line->getAmount());
            } else {
                $credits = $credits->add($line->getAmount());
            }
        }

        if (! $debits->equals($credits)) {
            throw new UnbalancedJournalEntryException($debits, $credits);
        }

        $this->lines = $lines;
    }
}
