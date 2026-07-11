<?php

namespace Accounting\Persistence;

use Accounting\Model\Account;
use Accounting\Model\AccountRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function find(int $id): Account
    {
        $account = $this->em->find(Account::class, $id);

        // Preserve the existing contract: callers (edit/delete controllers)
        // catch InvalidArgumentException when an id doesn't exist.
        if ($account === null) {
            throw new InvalidArgumentException(sprintf(
                'Account with identifier "%s" not found.',
                $id
            ));
        }

        return $account;
    }

    /** @return Account[] */
    public function all(): array
    {
        return $this->em->getRepository(Account::class)->findAll();
    }
}
