<?php

namespace Accounting\Model;

use Laminas\Db\ResultSet\HydratingResultSet;

interface AccountRepositoryInterface
{
    public function find(int $id): ?Account;
    /** @return Account[] */
    public function all(): HydratingResultSet;
    public function save(Account $account): Account;
}