<?php

namespace Accounting\Service;

use Accounting\Exceptions\IllegalTransitionException;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryCommandInterface;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\JournalEntryStatus;
use InvalidArgumentException;

class JournalEntryLifecycle
{
    public function __construct(
        private JournalEntryCommandInterface $command,
        private JournalEntryRepositoryInterface $journalEntryRepo,
    ) {}

    public function transitionTo(int $id, JournalEntryStatus $to): JournalEntry
    {
        $entry = $this->journalEntryRepo->find($id);

        if ($entry === null) {
            throw new InvalidArgumentException(sprintf('Journal entry "%d" not found.', $id));
        }

        return match ($to) {
            JournalEntryStatus::Submitted => $this->submitJournalEntry($entry),
            JournalEntryStatus::Approved  => $this->approveJournalEntry($entry),
            JournalEntryStatus::Posted    => $this->postJournalEntry($entry),
            JournalEntryStatus::Voided    => $this->voidJournalEntry($entry),
            default                       => throw new IllegalTransitionException($entry->getStatus(), $to),
        };
    }

    /** Submit a draft entry for approval and persist the new status. */
    public function submitJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->command->updateJournalEntry($journalEntry->submit());
    }

    /** Approve a submitted entry and persist the new status. */
    public function approveJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->command->updateJournalEntry($journalEntry->approve());
    }

    /** Post an approved entry to the ledger and persist the new status. */
    public function postJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->command->updateJournalEntry($journalEntry->post());
    }

    /** Void an entry and persist the new status. */
    public function voidJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->command->updateJournalEntry($journalEntry->void());
    }
}
