<?php

namespace Accounting\Controller;

use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntryRepositoryInterface;
use Laminas\Mvc\Controller\AbstractActionController;

class JournalListController extends AbstractActionController
{
    public function __construct(
        private JournalEntryRepositoryInterface $journalEntryRepo,
        private AccountRepositoryInterface $accountRepo,
    ) {}

    public function indexAction()
    {
        return [
            'journal_entries' => $this->journalEntryRepo->all(),
        ];
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        if (! $id) {
            return $this->redirect()->toRoute('journals');
        }

        $journalEntry = $this->journalEntryRepo->find($id);

        if ($journalEntry === null) {
            return $this->redirect()->toRoute('journals');
        }

        // Lines store only account ids; map id => name so the view can label them.
        $accountNames = [];
        foreach ($this->accountRepo->all() as $account) {
            $accountNames[$account->getAccountId()] = $account->getName();
        }

        return [
            'journal_entry' => $journalEntry,
            'account_names' => $accountNames,
        ];
    }
}
