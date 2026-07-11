<?php

declare(strict_types=1);

namespace AccountingTest\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Persistence\JournalEntryCommand;
use Accounting\Persistence\Type\MoneyType;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the Doctrine write path against in-memory SQLite.
 */
final class JournalEntryCommandTest extends TestCase
{
    private EntityManagerInterface $em;
    private JournalEntryCommand $command;

    protected function setUp(): void
    {
        if (! Type::hasType(MoneyType::NAME)) {
            Type::addType(MoneyType::NAME, MoneyType::class);
        }

        $config = ORMSetup::createXMLMetadataConfiguration(
            [__DIR__ . '/../../config/orm'],
            true,
        );
        $connection = DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config,
        );
        $this->em = new EntityManager($connection, $config);

        // Build the schema from our mappings.
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->command = new JournalEntryCommand($this->em);
    }

    private function draft(): JournalEntry
    {
        return new JournalEntry(
            null,
            new DateTimeImmutable('2026-01-01'),
            JournalEntryStatus::Draft,
            'Test entry',
            [
                new JournalEntryLine(null, 1, Direction::Debit, Money::fromMinor(1000)),
                new JournalEntryLine(null, 2, Direction::Credit, Money::fromMinor(1000)),
            ],
        );
    }

    public function testInsertPersistsRowWithGeneratedIdAndLines(): void
    {
        $saved = $this->command->insertJournalEntry($this->draft());

        $this->assertNotNull($saved->getJournalEntryId());
        $this->assertCount(2, $saved->getLines());

        // Re-read from a cleared EM to prove it round-tripped to the database.
        $this->em->clear();
        $reloaded = $this->em->find(JournalEntry::class, $saved->getJournalEntryId());
        $this->assertCount(2, $reloaded->getLines());
    }

    public function testUpdatePersistsStatusWithoutTouchingLines(): void
    {
        $saved   = $this->command->insertJournalEntry($this->draft());
        $lineIds = array_map(fn ($l) => $l->getJournalEntryLineId(), $saved->getLines());

        $this->command->updateJournalEntry($saved->submit());

        $this->em->clear();
        $reloaded = $this->em->find(JournalEntry::class, $saved->getJournalEntryId());

        $this->assertSame(JournalEntryStatus::Submitted, $reloaded->getStatus());
        $reloadedLineIds = array_map(fn ($l) => $l->getJournalEntryLineId(), $reloaded->getLines());
        sort($lineIds);
        sort($reloadedLineIds);
        $this->assertSame($lineIds, $reloadedLineIds, 'Lines untouched by a status update');
    }
}
