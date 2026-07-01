<?php

namespace Accounting\Service;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntryRepositoryInterface;

final class Ledger
{
    public function __construct(
        private AccountRepositoryInterface $accounts,
        private JournalEntryRepositoryInterface $journalEntries,
    ) {}

    public function balanceFor(int $accountId): Money
    {
        $account = $this->accounts->find($accountId);
        $balance = Money::zero();

        foreach ($this->journalEntries->all() as $journalEntry) {
            foreach ($journalEntry->getLines() as $line) {
                if ($line->getAccountId() !== $accountId) {
                    continue;
                }

                if ($line->getDirection() === $account->getType()->normalBalance()) {
                    $balance = $balance->add($line->getAmount());
                } else {
                    $balance = $balance->subtract($line->getAmount());
                }
            }
        }

        return $balance;
    }

    /**
     * Balance for every account, computed in a single pass over the journal
     * (cheaper than calling balanceFor() once per account).
     *
     * @return array<int, Money> keyed by account id
     */
    public function balances(): array
    {
        $normal   = [];
        $balances = [];

        foreach ($this->accounts->all() as $account) {
            $id            = $account->getAccountId();
            $normal[$id]   = $account->getType()->normalBalance();
            $balances[$id] = Money::zero();
        }

        foreach ($this->journalEntries->all() as $journalEntry) {
            foreach ($journalEntry->getLines() as $line) {
                $id = $line->getAccountId();

                if (! isset($balances[$id])) {
                    continue; // line references an account we don't know about
                }

                if ($line->getDirection() === $normal[$id]) {
                    $balances[$id] = $balances[$id]->add($line->getAmount());
                } else {
                    $balances[$id] = $balances[$id]->subtract($line->getAmount());
                }
            }
        }

        return $balances;
    }
}
