<?php

namespace Accounting\Model;

interface JournalEntryRepositoryInterface
{
    public function find(int $id): ?JournalEntry;
    /** @return JournalEntry[] */
    public function all(): array;
    public function save(JournalEntry $journalEntry): void;
}
