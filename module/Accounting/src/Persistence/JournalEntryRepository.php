<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

class JournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function __construct(private AdapterInterface $adapter) {}

    public function find(int $id): ?JournalEntry
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('journal_entry')->where(['journal_entry_id' => $id]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function all(): array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('journal_entry');
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $journalEntries = [];

        foreach ($results as $row) {
            $journalEntries[] = $this->hydrate($row);
        }

        return $journalEntries;
    }

    public function save(JournalEntry $journalEntry): void
    {
        $sql = new Sql($this->adapter);
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
            $journalEntry->setJournalEntryId((int) $result->getGeneratedValue());
        }

        $this->saveLines($journalEntry);
    }

    private function saveLines(JournalEntry $journalEntry): void
    {
        $sql = new Sql($this->adapter);
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

        $journalEntry = new JournalEntry();
        $journalEntry->setJournalEntryId($journalEntryId);
        $journalEntry->setDate(new DateTimeImmutable($row['date']));
        $journalEntry->setDescription($row['description']);
        $journalEntry->setLines($this->linesFor($journalEntryId));

        return $journalEntry;
    }

    /** @return JournalEntryLine[] */
    private function linesFor(int $journalEntryId): array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('journal_entry_line')->where(['journal_entry_id' => $journalEntryId]);
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $lines = [];

        foreach ($results as $row) {
            $line = new JournalEntryLine();
            $line->setJournalEntryLineId((int) $row['journal_entry_line_id']);
            $line->setJournalEntryId((int) $row['journal_entry_id']);
            $line->setAccountId((int) $row['account_id']);
            $line->setDirection(Direction::from($row['direction']));
            $line->setAmount(Money::fromMinor((int) $row['amount']));
            $lines[] = $line;
        }

        return $lines;
    }
}
