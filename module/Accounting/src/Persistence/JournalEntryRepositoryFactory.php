<?php

namespace Accounting\Persistence;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class JournalEntryRepositoryFactory
{
    public function __invoke(ContainerInterface $container): JournalEntryRepository
    {
        return new JournalEntryRepository(
            $container->get(AdapterInterface::class),
        );
    }
}
