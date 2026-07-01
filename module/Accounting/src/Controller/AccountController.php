<?php

namespace Accounting\Controller;

use Accounting\Model\AccountCommandInterface;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\Form\AccountForm;
use Accounting\Model\Account;
use Accounting\Service\Ledger;
use Accounting\ValueObject\AccountType;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use InvalidArgumentException;

class AccountController extends AbstractActionController
{
    public function __construct(
        private AccountCommandInterface $accountCommand,
        private AccountRepositoryInterface $accountRepo,
        private AccountForm $form,
        private Ledger $ledger,
    ) {}

    public function indexAction()
    {
        return [
            'accounts' => $this->accountRepo->all(),
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
            'account' => $this->accountRepo->find($id),
            'balance' => $this->ledger->balanceFor($id),
        ];
    }

    public function addAction()
    {
        $this->form->get('submit')->setValue('Add');

        $request = $this->getRequest();

        if (! $request->isPost()) {
            return ['form' => $this->form];
        }

        $this->form->setData($request->getPost());

        if (! $this->form->isValid()) {
            return ['form' => $this->form];
        }

        $data = $this->form->getData();
        $account = new Account(
            null,
            $data['name'],
            AccountType::from($data['account_type']),
        );
        $this->accountCommand->insertAccount($account);

        return $this->redirect()->toRoute('account');
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        if (! $id) {
            return $this->redirect()->toRoute('accounts', ['action' => 'add']);
        }

        try {
            $account = $this->accountRepo->find($id);
        } catch (InvalidArgumentException $ex) {
            return $this->redirect()->toRoute('account');
        }

        $this->form->setData([
            'account_id'   => $account->getAccountId(),
            'name'         => $account->getName(),
            'account_type' => $account->getType()->value,
        ]);
        $viewModel = new ViewModel(['form' => $this->form]);

        $request = $this->getRequest();
        if (! $request->isPost()) {
            return $viewModel;
        }

        $this->form->setData($request->getPost());

        if (! $this->form->isValid()) {
            return $viewModel;
        }

        $data = $this->form->getData();
        $account = new Account(
            $id,
            $data['name'],
            AccountType::from($data['account_type']),
        );
        $account = $this->accountCommand->updateAccount($account);
        
        // TODO: Try child routes as shown in docs
        return $this->redirect()->toRoute(
            'account',
            ['action' => 'view', 'id' => $account->getAccountId()]
        );
    }

    public function deleteAction()
    {

    }
}