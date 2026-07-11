<?php

namespace Accounting\Persistence;

use Accounting\Model\Account;
use Accounting\Model\AccountCommandInterface;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class AccountCommand implements AccountCommandInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function insertAccount(Account $account): Account
    {
        // persist() tells Doctrine to track this new object; flush() runs the
        // INSERT. Doctrine then writes the generated id back into $account,
        // so we can return the very same instance.
        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

    public function updateAccount(Account $account): Account
    {
        // The controller hands us a *detached* Account carrying the new values.
        // We load the *managed* one, copy the changes onto it, and flush.
        // Doctrine's unit-of-work computes the UPDATE by diffing — no SQL here.
        $managed = $this->em->find(Account::class, $account->getAccountId());

        if ($managed === null) {
            throw new RuntimeException('Cannot update account; not found');
        }

        $managed->rename($account->getName());
        $managed->changeType($account->getType());
        $this->em->flush();

        return $managed;
    }

    public function deleteAccount(Account $account): bool
    {
        if (! $account->getAccountId()) {
            throw new RuntimeException('Cannot delete account; missing identifier');
        }

        // The delete controller passes the entity it fetched via the repository,
        // which is already managed, so remove() + flush() is all we need.
        $this->em->remove($account);
        $this->em->flush();

        return true;
    }
}
