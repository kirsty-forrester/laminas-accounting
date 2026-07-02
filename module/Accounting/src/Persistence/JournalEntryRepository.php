<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use Accounting\ValueObject\JournalEntryStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Sql;

class JournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function __construct(private AdapterInterface $db) {}

    public function find(int $id): JournalEntry
    {
        $sql       = new Sql($this->db);
        $select    = $sql->select('journal_entry')->where(['journal_entry_id = ?' => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (! $result instanceof ResultInterface || ! $result->isQueryResult()) {
            throw new RuntimeException(sprintf(
                'Failed retrieving journal entry with identifier "%s"; unknown database error.',
                $id
            ));
        }

        $row = $result->current();

        if (! $row) {
            throw new InvalidArgumentException(sprintf(
                'Journal entry with identifier "%s" not found.',
                $id
            ));
        }

        return $this->hydrate($row);
    }

    /** @return JournalEntry[] */
    public function all(): array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('journal_entry');
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $entries = [];
        foreach ($results as $row) {
            $entries[] = $this->hydrate($row);
        }

        return $entries;
    }

    /**
     * Return posted journal entries
     * 
     * @return JournalEntry[]
     */
    public function posted(): array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('journal_entry')->where(['status' => JournalEntryStatus::Posted->value]);
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $entries = [];
        foreach ($results as $row) {
            $entries[] = $this->hydrate($row);
        }

        return $entries;
    }

    private function hydrate(array $row): JournalEntry
    {
        $journalEntryId = (int) $row['journal_entry_id'];

        return new JournalEntry(
            $journalEntryId,
            new DateTimeImmutable($row['date']),
            JournalEntryStatus::from($row['status']),
            $row['description'],
            $this->linesFor($journalEntryId),
        );
    }

    /** @return JournalEntryLine[] */
    private function linesFor(int $journalEntryId): array
    {
        $sql = new Sql($this->db);
        $select = $sql
            ->select('journal_entry_line')
            ->where(['journal_entry_id' => $journalEntryId]);
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
