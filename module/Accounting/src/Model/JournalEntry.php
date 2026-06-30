<?php

namespace Accounting\Model;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\Exceptions\UnbalancedJournalEntryException;
use DateTimeImmutable;

class JournalEntry
{
    /** @var JournalEntryLine[] */
    private array $lines;

    /** @param JournalEntryLine[] $lines */
    public function __construct(
        private ?int $journalEntryId,
        private DateTimeImmutable $date,
        private JournalEntryStatus $status,
        private string $description,
        array $lines,
    ) {
        $this->lines = $lines;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return JournalEntryLine[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @return JournalEntryStatus
     */
    public function getStatus(): JournalEntryStatus
    {
        return $this->status;
    }

    public function isBalanced(): bool
    {
        return $this->totalFor(Direction::Debit)->equals($this->totalFor(Direction::Credit));
    }

    private function withStatus(JournalEntryStatus $status): self
    {
        return new self($this->journalEntryId, $this->date, $status, $this->description, $this->lines);
    }

    private function totalFor(Direction $direction): Money
    {
        $total = Money::zero();

        foreach ($this->lines as $line) {
            if ($line->getDirection() === $direction) {
                $total = $total->add($line->getAmount());
            }
        }

        return $total;
    }

    private function guardCanTransitionTo(JournalEntryStatus $to): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw new IllegalTransitionException($this->status, $to);
        }
    }

    public function submit(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Submitted);

        if (! $this->isBalanced()) {
            throw new UnbalancedJournalEntryException(
                $this->totalFor(Direction::Debit),
                $this->totalFor(Direction::Credit),
            );
        }

        return $this->withStatus(JournalEntryStatus::Submitted);
    }

    public function approve(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Approved);

        return $this->withStatus(JournalEntryStatus::Approved);
    }

    public function post(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Posted);
        
        return $this->withStatus(JournalEntryStatus::Posted);
    }
    
    public function void(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Voided);
        
        return $this->withStatus(JournalEntryStatus::Voided);
    }
}
