<?php

namespace Accounting\Controller;

use Accounting\Form\JournalEntryForm;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use Laminas\Mvc\Controller\AbstractActionController;
use DateTimeImmutable;

class JournalWriteController extends AbstractActionController
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private JournalEntryRepositoryInterface $journalEntryRepo,
    ) {}

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

        return $this->redirect()->toRoute('journals');
    }

    // TODO: Move into a hydrator strategy
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