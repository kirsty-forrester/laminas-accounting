<?php

namespace Accounting\Controller;

use Accounting\Exceptions\IllegalTransitionException;
use Accounting\Exceptions\UnbalancedJournalEntryException;
use Accounting\Form\JournalEntryForm;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\Model\JournalEntry;
use Accounting\Model\JournalEntryLine;
use Accounting\Model\JournalEntryCommandInterface;
use Accounting\Model\JournalEntryRepositoryInterface;
use Accounting\Service\JournalEntryLifecycle;
use Accounting\ValueObject\Direction;
use Accounting\ValueObject\JournalEntryStatus;
use Accounting\ValueObject\Money;
use Laminas\Mvc\Controller\AbstractActionController;
use DateTimeImmutable;

class JournalWriteController extends AbstractActionController
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private JournalEntryCommandInterface $command,
        private JournalEntryRepositoryInterface $journalEntryRepo,
        private JournalEntryLifecycle $lifecycle
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
        $this->command->insertJournalEntry($journalEntry);

        return $this->redirect()->toRoute('journals');
    }

    /**
     * Apply a lifecycle transition (submit/approve/post/void) to an entry.
     */
    public function transitionAction()
    {
        $request = $this->getRequest();
        $id = (int) $this->params()->fromRoute('id');

        if (! $id || ! $request->isPost()) {
            return $this->redirect()->toRoute('journals');
        }

        $entry = $this->journalEntryRepo->find($id);
        if ($entry === null) {
            return $this->redirect()->toRoute('journals');
        }

        $to = (string) $request->getPost('to');

        try {
            match ($to) {
                JournalEntryStatus::Submitted->value => $this->lifecycle->submitJournalEntry($entry),
                JournalEntryStatus::Approved->value  => $this->lifecycle->approveJournalEntry($entry),
                JournalEntryStatus::Posted->value    => $this->lifecycle->postJournalEntry($entry),
                JournalEntryStatus::Voided->value    => $this->lifecycle->voidJournalEntry($entry),
                default                              => null,
            };
        } catch (IllegalTransitionException | UnbalancedJournalEntryException $e) {
            // TODO: surface via flash messenger
        }

        return $this->redirect()->toRoute('journals/view', ['id' => $id]);
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