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

    public function save(Account $account): Account
    {
        $sql = new Sql($this->db);
        $data = [
            'name' => $account->getName(),
            'type' => $account->getType()->value,
        ];

        if ($account->getAccountId()) {
            $update = $sql->update('account')->set($data)->where(['account_id' => $account->getAccountId()]);
            $sql->prepareStatementForSqlObject($update)->execute();

            return $account;
        }

        $insert = $sql->insert('account')->values($data);
        $result = $sql->prepareStatementForSqlObject($insert)->execute();

        // Entity is immutable (no setters); return a new instance carrying the generated id.
        return new Account(
            (int) $result->getGeneratedValue(),
            $account->getName(),
            $account->getType(),
        );
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
