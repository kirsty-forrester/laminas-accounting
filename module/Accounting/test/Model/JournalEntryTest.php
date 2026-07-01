<?php

declare(strict_types=1);

namespace AccountingTest\Model;

use Accounting\Exceptions\IllegalTransitionException;
use Accounting\Exceptions\UnbalancedJournalEntryException;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class JournalEntryTest extends TestCase
{
    private function line(int $accountId, Direction $direction, int $pennies): JournalEntryLine
    {
        return new JournalEntryLine(null, null, $accountId, $direction, Money::fromMinor($pennies));
    }

    /** @param JournalEntryLine[] $lines */
    private function entry(JournalEntryStatus $status, array $lines): JournalEntry
    {
        return new JournalEntry(null, new DateTimeImmutable('2026-01-01'), $status, 'Test entry', $lines);
    }

    /** A simple balanced pair: debit 1000 to account 1, credit 1000 to account 2. */
    private function balancedLines(): array
    {
        return [
            $this->line(1, Direction::Debit, 1000),
            $this->line(2, Direction::Credit, 1000),
        ];
    }

    public function testIsBalancedWhenDebitsEqualCredits(): void
    {
        $this->assertTrue($this->entry(JournalEntryStatus::Draft, $this->balancedLines())->isBalanced());
    }

    public function testTotalIsTheDebitTotal(): void
    {
        $lines = [
            $this->line(1, Direction::Debit, 2500),
            $this->line(2, Direction::Debit, 1500),
            $this->line(3, Direction::Credit, 4000),
        ];

        $this->assertSame(4000, $this->entry(JournalEntryStatus::Posted, $lines)->total()->pennies);
    }

    public function testIsNotBalancedWhenDebitsDifferFromCredits(): void
    {
        $lines = [
            $this->line(1, Direction::Debit, 1000),
            $this->line(2, Direction::Credit, 500),
        ];

        $this->assertFalse($this->entry(JournalEntryStatus::Draft, $lines)->isBalanced());
    }

    public function testSubmitBalancedDraftMovesToSubmitted(): void
    {
        $draft = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());

        $submitted = $draft->submit();

        $this->assertSame(JournalEntryStatus::Submitted, $submitted->getStatus());
    }

    public function testSubmitReturnsNewInstanceLeavingOriginalUnchanged(): void
    {
        $draft = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());

        $submitted = $draft->submit();

        $this->assertNotSame($draft, $submitted);
        $this->assertSame(JournalEntryStatus::Draft, $draft->getStatus());
    }

    public function testSubmitUnbalancedEntryThrows(): void
    {
        $lines = [
            $this->line(1, Direction::Debit, 1000),
            $this->line(2, Direction::Credit, 999),
        ];
        $draft = $this->entry(JournalEntryStatus::Draft, $lines);

        $this->expectException(UnbalancedJournalEntryException::class);

        $draft->submit();
    }

    public function testSubmitFromNonDraftStatusThrows(): void
    {
        $approved = $this->entry(JournalEntryStatus::Approved, $this->balancedLines());

        $this->expectException(IllegalTransitionException::class);

        $approved->submit();
    }

    public function testApproveFromSubmitted(): void
    {
        $submitted = $this->entry(JournalEntryStatus::Submitted, $this->balancedLines());

        $this->assertSame(JournalEntryStatus::Approved, $submitted->approve()->getStatus());
    }

    public function testApproveFromDraftThrows(): void
    {
        $draft = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());

        $this->expectException(IllegalTransitionException::class);

        $draft->approve();
    }

    public function testPostFromApproved(): void
    {
        $approved = $this->entry(JournalEntryStatus::Approved, $this->balancedLines());

        $this->assertSame(JournalEntryStatus::Posted, $approved->post()->getStatus());
    }

    public function testVoidFromDraft(): void
    {
        $draft = $this->entry(JournalEntryStatus::Draft, $this->balancedLines());

        $this->assertSame(JournalEntryStatus::Voided, $draft->void()->getStatus());
    }

    public function testVoidFromVoidedThrows(): void
    {
        $voided = $this->entry(JournalEntryStatus::Voided, $this->balancedLines());

        $this->expectException(IllegalTransitionException::class);

        $voided->void();
    }
}
