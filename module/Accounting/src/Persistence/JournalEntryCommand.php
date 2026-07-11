<?php

namespace Accounting\Persistence;

use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryCommandInterface;
use Doctrine\ORM\EntityManagerInterface;

class JournalEntryCommand implements JournalEntryCommandInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function insertJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        // cascade-persist saves the lines along with the entry in one flush.
        $this->em->persist($journalEntry);
        $this->em->flush();

        return $journalEntry;
    }

    public function updateJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        // In the transition flow the entry was loaded via the repository, so it's
        // already managed and its mutated status is tracked. flush() diffs it and
        // writes the UPDATE. No need to touch the lines, unlike the old SQL path.
        $this->em->flush();

        return $journalEntry;
    }
}
