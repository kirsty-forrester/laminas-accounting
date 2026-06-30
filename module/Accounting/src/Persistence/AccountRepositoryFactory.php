<?php

namespace Accounting\Persistence;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class AccountRepositoryFactory
{
    public function __invoke(ContainerInterface $container): AccountRepository
    {
        return new AccountRepository(
            $container->get(AdapterInterface::class),
        );
    }
}