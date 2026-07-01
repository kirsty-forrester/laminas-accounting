<?php

namespace Accounting\Persistence;

use Accounting\Model\Account;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\ValueObject\AccountType;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use InvalidArgumentException;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(private AdapterInterface $db) {}

    public function find(int $id): Account
    {
        $sql = new Sql($this->db);
        $select = $sql->select('account')->where(['account_id' => $id]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if (! $row) {
            throw new InvalidArgumentException(sprintf(
                'Account with identifier "%s" not found.',
                $id
            ));
        }

        return $this->hydrate($row);
    }

    /** @return Account[] */
    public function all(): array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('account');
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $accounts = [];
        foreach ($results as $row) {
            $accounts[] = $this->hydrate($row);
        }

        return $accounts;
    }

    private function hydrate(array $row): Account
    {
        return new Account(
            (int) $row['account_id'],
            $row['name'],
            AccountType::from($row['type']),
        );
    }
}
