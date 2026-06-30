<?php

namespace Accounting\Persistence;

use Accounting\Hydrator\Strategy\EnumStrategy;
use Accounting\Model\Account;
use Accounting\ValueObject\AccountType;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Laminas\Hydrator\ReflectionHydrator;
use Psr\Container\ContainerInterface;

class AccountRepositoryFactory
{
    public function __invoke(ContainerInterface $container): AccountRepository
    {
        $hydrator = new ReflectionHydrator();
        $hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
        $hydrator->addStrategy('type', new EnumStrategy(AccountType::class));

        return new AccountRepository(
            $container->get(AdapterInterface::class),
            $hydrator,
            new Account(null, '', AccountType::Asset),
        );
    }
}
