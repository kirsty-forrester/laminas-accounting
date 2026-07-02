<?php

namespace Accounting\Service;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\JournalEntryStatus;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

class JournalEntryLifecycle
{
    public function __construct(
        private AdapterInterface $db,
        private JournalEntryRepositoryInterface $journalEntryRepo
    ) {}

    public function transitionTo(int $id, JournalEntryStatus $to): JournalEntry
    {
        $entry = $this->journalEntryRepo->find($id);

        return match ($to) {
            JournalEntryStatus::Submitted => $this->submitJournalEntry($entry),
            JournalEntryStatus::Approved  => $this->approveJournalEntry($entry),
            JournalEntryStatus::Posted    => $this->postJournalEntry($entry),
            JournalEntryStatus::Voided    => $this->voidJournalEntry($entry),
            default => throw new IllegalTransitionException($entry->getStatus(), $to),
        };
    }

    /**
     * Submit a draft entry for approval and persist the new status.
     *
     * @return JournalEntry The entry in its submitted state.
     */
    public function submitJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->persistStatus($journalEntry->submit());
    }

    /**
     * Approve a submitted entry and persist the new status.
     *
     * @return JournalEntry The entry in its approved state.
     */
    public function approveJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->persistStatus($journalEntry->approve());
    }

    /**
     * Post an approved entry to the ledger and persist the new status.
     *
     * @return JournalEntry The entry in its posted state.
     */
    public function postJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->persistStatus($journalEntry->post());
    }

    /**
     * Void an entry and persist the new status.
     *
     * @return JournalEntry The entry in its voided state.
     */
    public function voidJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        return $this->persistStatus($journalEntry->void());
    }

    /**
     * Persist just the status column for an entry whose status has changed.
     * Lines are untouched by a lifecycle transition.
     */
    private function persistStatus(JournalEntry $journalEntry): JournalEntry
    {
        $sql = new Sql($this->db);
        $update = $sql->update('journal_entry')
            ->set(['status' => $journalEntry->getStatus()->value])
            ->where(['journal_entry_id' => $journalEntry->getJournalEntryId()]);
        $sql->prepareStatementForSqlObject($update)->execute();

        return $journalEntry;
    }
}