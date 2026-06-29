<?php

namespace Accounting\Repository;

use Accounting\Model\Account;
use Accounting\ValueObject\AccountType;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(private AdapterInterface $adapter) {}

    public function find(int $id): ?Account
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('account')->where(['account.id' => $id]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function all(): array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select('account');
        $results = $sql->prepareStatementForSqlObject($select)->execute();

        $accounts = [];

        foreach ($results as $row) {
            $accounts[] = $this->hydrate($row);
        }

        return $accounts;
    }

    public function save(Account $account): void {
        $sql = new Sql($this->adapter);
        $data = [
            'name' => $account->getName(),
            'type' => $account->getType()->value,
        ];

        if ($account->getAccountId()) {
            $update = $sql->update('account')->set($data)->where(['account_id' => $account->getAccountId()]);
        } else {
            $insert = $sql->insert('account')->values($data);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }
    }

    private function hydrate(array $row): Account
    {
        $account = new Account();
        $account->setAccountId((int) $row['account_id']);
        $account->setName($row['name']);
        $account->setType(AccountType::from($row['type']));
        return $account;
    }
}