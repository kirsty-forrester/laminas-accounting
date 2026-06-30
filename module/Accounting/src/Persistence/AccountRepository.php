<?php

namespace Accounting\Persistence;

use Accounting\Model\Account;
use Accounting\Model\AccountRepositoryInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use InvalidArgumentException;
use RuntimeException;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        private AdapterInterface $db,
        private HydratorInterface $hydrator,
        private Account $accountPrototype,
    ) {}

    public function find(int $id): Account
    {
        $sql       = new Sql($this->db);
        $select    = $sql->select('account');
        $select->where(['account_id = ?' => $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (! $result instanceof ResultInterface || ! $result->isQueryResult()) {
            throw new RuntimeException(sprintf(
                'Failed retrieving account with identifier "%s"; unknown database error.',
                $id
            ));
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountPrototype);
        $resultSet->initialize($result);
        $account = $resultSet->current();

        if (! $account) {
            throw new InvalidArgumentException(sprintf(
                'Account with identifier "%s" not found.',
                $id
            ));
        }

        return $account;
    }

    public function all(): HydratingResultSet
    {
        $resultSet = new HydratingResultSet($this->hydrator, $this->accountPrototype);

        $sql    = new Sql($this->db);
        $select = $sql->select('account');
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet->initialize($result);
        }

        return $resultSet;
    }

    public function save(Account $account): Account {
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
}
