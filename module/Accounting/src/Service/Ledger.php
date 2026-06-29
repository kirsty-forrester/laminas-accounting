<?php

namespace Accounting\Service;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use Accounting\Repository\AccountRepositoryInterface;
use Accounting\Repository\JournalEntryRepositoryInterface;

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
}
