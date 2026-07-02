<?php

declare(strict_types=1);

namespace AccountingTest\Service;

use Accounting\Exceptions\IllegalTransitionException;
use Accounting\Exceptions\UnbalancedJournalEntryException;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryCommandInterface;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\Service\JournalEntryLifecycle;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests: the lifecycle now persists through the command (a gateway we can
 * fake), so no database is required.
 */
final class JournalEntryLifecycleTest extends TestCase
{
    /** A command spy that records what it was asked to persist. */
    private function fakeCommand(): JournalEntryCommandInterface
    {
        return new class implements JournalEntryCommandInterface {
            /** @var JournalEntry[] */
            public array $updated = [];

            public function insertJournalEntry(JournalEntry $journalEntry): JournalEntry
            {
                return $journalEntry;
            }

            public function updateJournalEntry(JournalEntry $journalEntry): JournalEntry
            {
                $this->updated[] = $journalEntry;
                return $journalEntry;
            }
        };
    }

    /** @param array<int, JournalEntry> $byId */
    private function fakeRepo(array $byId): JournalEntryRepositoryInterface
    {
        return new class ($byId) implements JournalEntryRepositoryInterface {
            /** @param array<int, JournalEntry> $byId */
            public function __construct(private array $byId) {}

            public function find(int $id): ?JournalEntry
            {
                return $this->byId[$id] ?? null;
            }

            public function all(): array
            {
                return array_values($this->byId);
            }

            public function posted(): array
            {
                return [];
            }
        };
    }

    private function line(Direction $direction, int $pennies): JournalEntryLine
    {
        return new JournalEntryLine(null, null, 1, $direction, Money::fromMinor($pennies));
    }

    /** @param JournalEntryLine[] $lines */
    private function entry(JournalEntryStatus $status, array $lines, ?int $id = 1): JournalEntry
    {
        return new JournalEntry($id, new DateTimeImmutable('2026-01-01'), $status, 'Test', $lines);
    }

    private function balancedLines(): array
    {
        return [$this->line(Direction::Debit, 1000), $this->line(Direction::Credit, 1000)];
    }

    public function testSubmitReturnsSubmittedAndPersistsViaCommand(): void
    {
        $command   = $this->fakeCommand();
        $lifecycle = new JournalEntryLifecycle($command, $this->fakeRepo([]));

        $result = $lifecycle->submitJournalEntry($this->entry(JournalEntryStatus::Draft, $this->balancedLines()));

        $this->assertSame(JournalEntryStatus::Submitted, $result->getStatus());
        $this->assertCount(1, $command->updated);
        $this->assertSame(JournalEntryStatus::Submitted, $command->updated[0]->getStatus());
    }

    public function testTransitionToLoadsThenDispatches(): void
    {
        $draft   = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());
        $command = $this->fakeCommand();
        $lifecycle = new JournalEntryLifecycle($command, $this->fakeRepo([1 => $draft]));

        $result = $lifecycle->transitionTo(1, JournalEntryStatus::Submitted);

        $this->assertSame(JournalEntryStatus::Submitted, $result->getStatus());
    }

    public function testFullApprovalChain(): void
    {
        $lifecycle = new JournalEntryLifecycle($this->fakeCommand(), $this->fakeRepo([]));

        $draft     = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());
        $posted    = $lifecycle->postJournalEntry(
            $lifecycle->approveJournalEntry(
                $lifecycle->submitJournalEntry($draft)
            )
        );

        $this->assertSame(JournalEntryStatus::Posted, $posted->getStatus());
    }

    public function testApprovingADraftIsIllegalAndDoesNotPersist(): void
    {
        $command   = $this->fakeCommand();
        $lifecycle = new JournalEntryLifecycle($command, $this->fakeRepo([]));

        try {
            $lifecycle->approveJournalEntry($this->entry(JournalEntryStatus::Draft, $this->balancedLines()));
            $this->fail('Expected IllegalTransitionException');
        } catch (IllegalTransitionException) {
            $this->assertSame([], $command->updated, 'Nothing should be persisted on a rejected transition');
        }
    }

    public function testSubmittingAnUnbalancedEntryThrowsAndDoesNotPersist(): void
    {
        $command   = $this->fakeCommand();
        $lifecycle = new JournalEntryLifecycle($command, $this->fakeRepo([]));

        $unbalanced = $this->entry(JournalEntryStatus::Draft, [
            $this->line(Direction::Debit, 1000),
            $this->line(Direction::Credit, 500),
        ]);

        try {
            $lifecycle->submitJournalEntry($unbalanced);
            $this->fail('Expected UnbalancedJournalEntryException');
        } catch (UnbalancedJournalEntryException) {
            $this->assertSame([], $command->updated);
        }
    }

    public function testTransitionToUnknownEntryThrows(): void
    {
        $lifecycle = new JournalEntryLifecycle($this->fakeCommand(), $this->fakeRepo([]));

        $this->expectException(InvalidArgumentException::class);

        $lifecycle->transitionTo(999, JournalEntryStatus::Submitted);
    }

    public function testTransitionToDraftIsIllegal(): void
    {
        $draft     = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());
        $lifecycle = new JournalEntryLifecycle($this->fakeCommand(), $this->fakeRepo([1 => $draft]));

        $this->expectException(IllegalTransitionException::class);

        $lifecycle->transitionTo(1, JournalEntryStatus::Draft);
    }
}
