<?php

declare(strict_types=1);

namespace AccountingTest\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Persistence\JournalEntryCommand;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the SQL write path — the one place the persistence
 * actually hits a database. Uses in-memory SQLite.
 */
final class JournalEntryCommandTest extends TestCase
{
    private Adapter $adapter;
    private JournalEntryCommand $command;

    protected function setUp(): void
    {
        $this->adapter = new Adapter(['driver' => 'Pdo_Sqlite', 'database' => ':memory:']);

        foreach ([
            'CREATE TABLE journal_entry (
                journal_entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT NOT NULL,
                description TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\'
            )',
            'CREATE TABLE journal_entry_line (
                journal_entry_line_id INTEGER PRIMARY KEY AUTOINCREMENT,
                journal_entry_id INTEGER NOT NULL,
                account_id INTEGER NOT NULL,
                direction TEXT NOT NULL,
                amount INTEGER NOT NULL
            )',
        ] as $ddl) {
            $this->adapter->query($ddl, Adapter::QUERY_MODE_EXECUTE);
        }

        $this->command = new JournalEntryCommand($this->adapter);
    }

    private function draft(?int $id = null): JournalEntry
    {
        return new JournalEntry(
            $id,
            new DateTimeImmutable('2026-01-01'),
            JournalEntryStatus::Draft,
            'Test entry',
            [
                new JournalEntryLine(null, null, 1, Direction::Debit, Money::fromMinor(1000)),
                new JournalEntryLine(null, null, 2, Direction::Credit, Money::fromMinor(1000)),
            ],
        );
    }

    /** @return array<int, string> line id => direction */
    private function storedLines(int $entryId): array
    {
        $rows = $this->adapter->query(
            "SELECT journal_entry_line_id, direction FROM journal_entry_line WHERE journal_entry_id = $entryId",
            Adapter::QUERY_MODE_EXECUTE
        );

        $lines = [];
        foreach ($rows as $row) {
            $lines[(int) $row['journal_entry_line_id']] = $row['direction'];
        }
        return $lines;
    }

    public function testInsertPersistsRowWithGeneratedIdAndLines(): void
    {
        $saved = $this->command->insertJournalEntry($this->draft());

        $this->assertNotNull($saved->getJournalEntryId());
        $this->assertCount(2, $this->storedLines($saved->getJournalEntryId()));
    }

    public function testUpdatePersistsStatusWithoutTouchingLines(): void
    {
        $saved = $this->command->insertJournalEntry($this->draft());
        $id    = $saved->getJournalEntryId();

        $lineIdsBefore = array_keys($this->storedLines($id));

        // Transition to submitted and persist via the command.
        $this->command->updateJournalEntry($saved->submit());

        $row = $this->adapter->query(
            "SELECT status FROM journal_entry WHERE journal_entry_id = $id",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $this->assertSame('submitted', $row['status']);
        // Lines must be left alone — same ids, not deleted/reinserted.
        $this->assertSame($lineIdsBefore, array_keys($this->storedLines($id)));
    }
}
