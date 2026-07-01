<?php

namespace Accounting\Controller;

use Accounting\ValueObject\Direction;
use Accounting\ValueObject\Money;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Form\JournalEntryForm;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntryRepositoryInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use DateTimeImmutable;

class JournalController extends AbstractActionController
{
    public function __construct(private AccountRepositoryInterface $accountRepo, private JournalEntryRepositoryInterface $journalEntryRepo) {}

    public function indexAction()
    {
        return [
            'journal_entries' => $this->journalEntryRepo->all(),
        ];
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('journal');
        }

        $journalEntry = $this->journalEntryRepo->find($id);

        if ($journalEntry === null) {
            return $this->redirect()->toRoute('journal');
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

    public function addAction()
    {
        $form = new JournalEntryForm($this->accountRepo->all());
        $request = $this->getRequest();

        if (! $request->isPost()) {
            return ['form' => $form];
        }

        $form->setData($request->getPost());

        if (! $form->isValid()) {
            return ['form' => $form];
        }

        $data = $form->getData();
        $journalEntry = new JournalEntry(
            null,
            new DateTimeImmutable($data['date']),
            JournalEntryStatus::Draft,
            $data['description'],
            $this->mapLines($data['lines'])
        );
        $this->journalEntryRepo->save($journalEntry);

        return $this->redirect()->toRoute('journal');
    }

    /** @param array $rows the posted $data['lines'] */
    private function mapLines(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $debit  = trim((string) ($row['debit'] ?? ''));
            $credit = trim((string) ($row['credit'] ?? ''));

            // The filled column decides the direction.
            if ($debit !== '') {
                $direction = Direction::Debit;
                $amount    = Money::fromDecimal($debit);
            } else {
                $direction = Direction::Credit;
                $amount    = Money::fromDecimal($credit);
            }

            $lineId = ($row['journal_entry_line_id'] ?? '') !== ''
                ? (int) $row['journal_entry_line_id']
                : null;

            $lines[] = new JournalEntryLine(
                $lineId,
                null,                     // journal_entry_id assigned on save
                (int) $row['account_id'],
                $direction,
                $amount,
            );
        }

        return $lines;
    }
}