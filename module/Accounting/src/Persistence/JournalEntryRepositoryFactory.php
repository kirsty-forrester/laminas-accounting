<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\Hydrator\Strategy\EnumStrategy;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Laminas\Hydrator\ReflectionHydrator;
use Psr\Container\ContainerInterface;
use DateTimeImmutable;

class JournalEntryRepositoryFactory
{
    public function __invoke(ContainerInterface $container): JournalEntryRepository
    {
        $hydrator = new ReflectionHydrator();
        $hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
        $hydrator->addStrategy('status', new EnumStrategy(JournalEntryStatus::class));

        return new JournalEntryRepository(
            $container->get(AdapterInterface::class),
            $hydrator,
            new JournalEntry(
                null,
                new DateTimeImmutable(),
                JournalEntryStatus::Draft,
                '',
                []
            )
        );
    }
}
