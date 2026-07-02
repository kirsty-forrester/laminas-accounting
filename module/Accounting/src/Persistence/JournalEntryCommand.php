<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryCommandInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

class JournalEntryCommand implements JournalEntryCommandInterface
{
    public function __construct(private AdapterInterface $db) {}

    /**
     * {@inheritDoc}
     */
    public function insertJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        $sql = new Sql($this->db);
        $data = [
            'date'        => $journalEntry->getDate()->format('Y-m-d'),
            'description' => $journalEntry->getDescription(),
            'status'      => $journalEntry->getStatus()->value,
        ];

        $insert = $sql->insert('journal_entry')->values($data);
        $result = $sql->prepareStatementForSqlObject($insert)->execute();

        // Entity is immutable (no setters); rebuild it with the generated id.
        $journalEntry = new JournalEntry(
            (int) $result->getGeneratedValue(),
            $journalEntry->getDate(),
            $journalEntry->getStatus(),
            $journalEntry->getDescription(),
            $journalEntry->getLines(),
        );

        $this->saveLines($journalEntry);

        return $journalEntry;
    }

    private function saveLines(JournalEntry $journalEntry): void
    {
        $sql = new Sql($this->db);
        $journalEntryId = $journalEntry->getJournalEntryId();

        // Replace the line set wholesale — simplest way to keep lines in sync.
        $delete = $sql->delete('journal_entry_line')->where(['journal_entry_id' => $journalEntryId]);
        $sql->prepareStatementForSqlObject($delete)->execute();

        foreach ($journalEntry->getLines() as $line) {
            $insert = $sql->insert('journal_entry_line')->values([
                'journal_entry_id' => $journalEntryId,
                'account_id'       => $line->getAccountId(),
                'direction'        => $line->getDirection()->value,
                'amount'           => $line->getAmount()->pennies,
            ]);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }
    }
}