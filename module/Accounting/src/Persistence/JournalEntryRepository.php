<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\JournalEntryStatus;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class JournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function find(int $id): JournalEntry
    {
        $entry = $this->em->find(JournalEntry::class, $id);

        if ($entry === null) {
            throw new InvalidArgumentException(sprintf(
                'Journal entry with identifier "%s" not found.',
                $id
            ));
        }

        return $entry;
    }

    /** @return JournalEntry[] */
    public function all(): array
    {
        return $this->em->getRepository(JournalEntry::class)->findAll();
    }

    /** @return JournalEntry[] */
    public function posted(): array
    {
        // We pass the enum's backing value, not the enum object: findBy binds
        // criteria through the column's *string* DBAL type, which expects the
        // scalar. (Writing an entity converts the enum for you; querying doesn't.)
        return $this->em->getRepository(JournalEntry::class)
            ->findBy(['status' => JournalEntryStatus::Posted->value]);
    }
}
