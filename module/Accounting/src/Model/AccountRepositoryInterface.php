<?php

namespace Accounting\Model;

interface AccountRepositoryInterface
{
    public function find(int $id): Account;
    /** @return Account[] */
    public function all(): array;
    public function save(Account $account): Account;
}
