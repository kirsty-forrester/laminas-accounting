<?php

declare(strict_types=1);

namespace AccountingTest\Service;

use Accounting\Model\Account;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\Service\Ledger;
use Accounting\ValueObject\AccountType;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LedgerTest extends TestCase
{
    /** @param Account[] $accounts */
    private function fakeAccounts(array $accounts): AccountRepositoryInterface
    {
        return new class ($accounts) implements AccountRepositoryInterface {
            /** @param Account[] $accounts keyed by account id */
            public function __construct(private array $accounts) {}

            public function find(int $id): Account
            {
                return $this->accounts[$id];
            }

            public function all(): array
            {
                return array_values($this->accounts);
            }

            public function save(Account $account): Account
            {
                return $account;
            }
        };
    }

    /** @param JournalEntry[] $entries */
    private function fakeJournalEntries(array $entries): JournalEntryRepositoryInterface
    {
        return new class ($entries) implements JournalEntryRepositoryInterface {
            /** @param JournalEntry[] $entries */
            public function __construct(private array $entries) {}

            public function find(int $id): JournalEntry
            {
                return $this->entries[$id]
                    ?? throw new \InvalidArgumentException(sprintf('Journal entry "%d" not found.', $id));
            }

            public function all(): array
            {
                return $this->entries;
            }

            public function posted(): array
            {
                return $this->entries;
            }

            public function save(JournalEntry $journalEntry): JournalEntry
            {
                return $journalEntry;
            }
        };
    }

    private function line(int $accountId, Direction $direction, int $pennies): JournalEntryLine
    {
        return new JournalEntryLine(null, null, $accountId, $direction, Money::fromMinor($pennies));
    }

    /** @param JournalEntryLine[] $lines */
    private function entry(array $lines): JournalEntry
    {
        return new JournalEntry(null, new DateTimeImmutable('2026-01-01'), JournalEntryStatus::Posted, 'Test', $lines);
    }

    public function testBalanceForDebitNormalAccountAddsDebitsAndSubtractsCredits(): void
    {
        // Account 1 is an asset (debit-normal), e.g. Cash at Bank.
        $accounts = $this->fakeAccounts([
            1 => new Account(1, 'Cash at Bank', AccountType::Asset),
        ]);

        $entries = $this->fakeJournalEntries([
            // £100 in: debit cash, credit some income account (2).
            $this->entry([
                $this->line(1, Direction::Debit, 10000),
                $this->line(2, Direction::Credit, 10000),
            ]),
            // £30 out: credit cash, debit some expense account (3).
            $this->entry([
                $this->line(3, Direction::Debit, 3000),
                $this->line(1, Direction::Credit, 3000),
            ]),
        ]);

        $ledger = new Ledger($accounts, $entries);

        // 10000 (debit) - 3000 (credit) = 7000.
        $this->assertSame(7000, $ledger->balanceFor(1)->pennies);
    }

    public function testBalanceIgnoresLinesForOtherAccounts(): void
    {
        $accounts = $this->fakeAccounts([
            1 => new Account(1, 'Cash at Bank', AccountType::Asset),
        ]);

        $entries = $this->fakeJournalEntries([
            $this->entry([
                $this->line(2, Direction::Debit, 5000),
                $this->line(3, Direction::Credit, 5000),
            ]),
        ]);

        $ledger = new Ledger($accounts, $entries);

        $this->assertSame(0, $ledger->balanceFor(1)->pennies);
    }

    public function testBalancesComputesEveryAccountInOnePass(): void
    {
        $accounts = $this->fakeAccounts([
            1 => new Account(1, 'Cash at Bank', AccountType::Asset),   // debit-normal
            5 => new Account(5, 'Sales Revenue', AccountType::Income), // credit-normal
        ]);

        $entries = $this->fakeJournalEntries([
            // £80 sale: debit cash, credit revenue.
            $this->entry([
                $this->line(1, Direction::Debit, 8000),
                $this->line(5, Direction::Credit, 8000),
            ]),
        ]);

        $balances = (new Ledger($accounts, $entries))->balances();

        $this->assertSame(8000, $balances[1]->pennies);
        $this->assertSame(8000, $balances[5]->pennies);
    }

    public function testBalancesReturnsZeroForAccountsWithNoLines(): void
    {
        $accounts = $this->fakeAccounts([
            9 => new Account(9, 'Petty Cash', AccountType::Asset),
        ]);

        $balances = (new Ledger($accounts, $this->fakeJournalEntries([])))->balances();

        $this->assertSame(0, $balances[9]->pennies);
    }

    public function testBalanceForCreditNormalAccountAddsCreditsAndSubtractsDebits(): void
    {
        // Account 5 is income (credit-normal): credits increase it.
        $accounts = $this->fakeAccounts([
            5 => new Account(5, 'Sales Revenue', AccountType::Income),
        ]);

        $entries = $this->fakeJournalEntries([
            $this->entry([
                $this->line(1, Direction::Debit, 8000),
                $this->line(5, Direction::Credit, 8000),
            ]),
        ]);

        $ledger = new Ledger($accounts, $entries);

        $this->assertSame(8000, $ledger->balanceFor(5)->pennies);
    }
}
