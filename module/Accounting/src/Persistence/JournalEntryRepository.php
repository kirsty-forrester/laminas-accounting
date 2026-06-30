<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Sql;

class JournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function __construct(
        private AdapterInterface $db,
        private HydratorInterface $hydrator,
        private JournalEntry $journalEntryPrototype,
    ) {}

    public function find(int $id): ?JournalEntry
    {
        $sql = new Sql($this->db);
        $select = $sql->select('journal_entry')->where(['journal_entry_id' => $id]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function all(): HydratingResultSet
    {
        $resultSet = new HydratingResultSet($this->hydrator, $this->journalEntryPrototype);

        $sql    = new Sql($this->db);
        $select = $sql->select('journal_entry');
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet->initialize($result);
        }

        return $resultSet;
    }

    public function save(JournalEntry $journalEntry): JournalEntry
    {
        $sql = new Sql($this->db);
        $data = [
            'date' => $journalEntry->getDate()->format('Y-m-d'),
            'description' => $journalEntry->getDescription(),
        ];

        if ($journalEntry->getJournalEntryId()) {
            $update = $sql->update('journal_entry')
                ->set($data)
                ->where(['journal_entry_id' => $journalEntry->getJournalEntryId()]);
            $sql->prepareStatementForSqlObject($update)->execute();
        } else {
            $insert = $sql->insert('journal_entry')->values($data);
            $result = $sql->prepareStatementForSqlObject($insert)->execute();

            // Entity is immutable (no setters); rebuild it with the generated id.
            $journalEntry = new JournalEntry(
                (int) $result->getGeneratedValue(),
                $journalEntry->getDate(),
                $journalEntry->getDescription(),
                $journalEntry->getLines(),
            );
        }

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
                'account_id' => $line->getAccountId(),
                'direction' => $line->getDirection()->value,
                'amount' => $line->getAmount()->pennies,
            ]);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }
    }

    private function hydrate(array $row): JournalEntry
    {
        $journalEntryId = (int) $row['journal_entry_id'];

        return new JournalEntry(
            $journalEntryId,
            new DateTimeImmutable($row['date']),
            $row['description'],
            $this->linesFor($journalEntryId),
        );
    }

    /** @return JournalEntryLine[] */
    private function linesFor(int $journalEntryId): array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('journal_entry_line')->where(['journal_entry_id' => $journalEntryId]);
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $lines = [];

        foreach ($results as $row) {
            $lines[] = new JournalEntryLine(
                (int) $row['journal_entry_line_id'],
                (int) $row['journal_entry_id'],
                (int) $row['account_id'],
                Direction::from($row['direction']),
                Money::fromMinor((int) $row['amount']),
            );
        }

        return $lines;
    }
}
