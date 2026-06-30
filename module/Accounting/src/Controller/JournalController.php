<?php

namespace Accounting\Controller;

use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntryRepositoryInterface;
use Laminas\Mvc\Controller\AbstractActionController;

class JournalController extends AbstractActionController
{
    public function __construct(private AccountRepositoryInterface $accountRepo, private JournalEntryRepositoryInterface $journalEntryRepo) {}

    public function indexAction()
    {
        return [
            'journal_entries' => $this->journalEntryRepo->all(),
        ];
    }

    public function addAction()
    {
        // Materialise to an array: the view iterates the account list several
        // times (one <select> per line), but all() returns a forward-only
        // HydratingResultSet that can only be walked once.
        return ['accounts' => iterator_to_array($this->accountRepo->all())];
    }
}