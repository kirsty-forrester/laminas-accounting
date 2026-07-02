<?php

namespace Accounting\Model;
 
interface JournalEntryCommandInterface
{
    /**
     * Persist a new journal entry in the system.
     *
     * @param JournalEntry $journalEntry The journal entry to insert; may or may not have an identifier.
     * @return JournalEntry The inserted journal entry, with identifier.
     */
    public function insertJournalEntry(JournalEntry $journalEntry): JournalEntry;
}