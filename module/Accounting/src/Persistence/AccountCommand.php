<?php

namespace Accounting\Persistence;

use Accounting\Model\AccountCommandInterface;
use Accounting\Model\Account;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

class AccountCommand implements AccountCommandInterface
{
    public function __construct(private AdapterInterface $db) {}

    /**
     * {@inheritDoc}
     */
    public function insertAccount(Account $account): Account
    {
        $sql = new Sql($this->db);
        $data = [
            'name' => $account->getName(),
            'type' => $account->getType()->value,
        ];

        $insert = $sql->insert('account')->values($data);
        $result = $sql->prepareStatementForSqlObject($insert)->execute();

        // Entity is immutable (no setters); return a new instance carrying the generated id.
        return new Account(
            (int) $result->getGeneratedValue(),
            $account->getName(),
            $account->getType(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function updateAccount(Account $account): Account
    {
        $sql = new Sql($this->db);
        $data = [
            'name' => $account->getName(),
            'type' => $account->getType()->value,
        ];

        if ($account->getAccountId()) {
            $update = $sql->update('account')->set($data)->where(['account_id' => $account->getAccountId()]);
            $sql->prepareStatementForSqlObject($update)->execute();
        }

        return $account;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAccount(Account $account)
    {
        
    }
}