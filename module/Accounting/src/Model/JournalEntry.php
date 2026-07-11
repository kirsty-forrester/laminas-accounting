<?php

namespace Accounting\Model;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\Exceptions\IllegalTransitionException;
use Accounting\Exceptions\UnbalancedJournalEntryException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use DateTimeImmutable;

class JournalEntry
{
    /** @var Collection<int, JournalEntryLine> */
    private Collection $lines;

    /** @param JournalEntryLine[] $lines */
    public function __construct(
        private ?int $journalEntryId,
        private DateTimeImmutable $date,
        private JournalEntryStatus $status,
        private string $description,
        array $lines = [],
    ) {
        // Doctrine bypasses this constructor when loading from the DB (it injects
        // a PersistentCollection directly). This runs only for *new* entries built
        // in app code, where we wrap the array and wire up the back-references.
        $this->lines = new ArrayCollection();
        foreach ($lines as $line) {
            $this->addLine($line);
        }
    }

    /** Keeps both sides of the association in sync — the line's FK is the owning side. */
    public function addLine(JournalEntryLine $line): void
    {
        $line->assignToEntry($this);
        $this->lines->add($line);
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

    /** @return JournalEntryLine[] */
    public function getLines(): array
    {
        return $this->lines->toArray();
    }

    public function getStatus(): JournalEntryStatus
    {
        return $this->status;
    }

    public function isBalanced(): bool
    {
        return $this->totalFor(Direction::Debit)->equals($this->totalFor(Direction::Credit));
    }

    public function total(): Money
    {
        return $this->totalFor(Direction::Debit);
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

        $this->status = JournalEntryStatus::Submitted; // mutate in place
        return $this;
    }

    public function approve(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Approved);
        $this->status = JournalEntryStatus::Approved;
        return $this;
    }

    public function post(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Posted);
        $this->status = JournalEntryStatus::Posted;
        return $this;
    }

    public function void(): self
    {
        $this->guardCanTransitionTo(JournalEntryStatus::Voided);
        $this->status = JournalEntryStatus::Voided;
        return $this;
    }
}
