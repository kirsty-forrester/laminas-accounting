<?php

namespace Accounting\Controller;

use Accounting\Model\AccountRepositoryInterface;
use Accounting\Service\Ledger;
use Laminas\Mvc\Controller\AbstractActionController;

class AccountListController extends AbstractActionController
{
    public function __construct(
        private AccountRepositoryInterface $repository,
        private Ledger $ledger
    ) {}

    public function indexAction()
    {
        return [
            'accounts' => $this->repository->all(),
            'balances' => $this->ledger->balances(),
        ];
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        if (! $id) {
            return $this->redirect()->toRoute('accounts');
        }

        return [
            'account' => $this->repository->find($id),
            'balance' => $this->ledger->balanceFor($id),
        ];
    }
}