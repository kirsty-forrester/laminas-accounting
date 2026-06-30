<?php

namespace Accounting\Model;

use Laminas\Db\ResultSet\HydratingResultSet;

interface JournalEntryRepositoryInterface
{
    public function find(int $id): ?JournalEntry;
    /** @return JournalEntry[] */
    public function all(): HydratingResultSet;
    public function save(JournalEntry $journalEntry): JournalEntry;
}
