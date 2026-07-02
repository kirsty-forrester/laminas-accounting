<?php

namespace Accounting\Controller;

use Accounting\Form\AccountForm;
use Accounting\Model\Account;
use Accounting\Model\AccountCommandInterface;
use Accounting\Model\AccountRepositoryInterface;
use Accounting\ValueObject\AccountType;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use InvalidArgumentException;

class AccountWriteController extends AbstractActionController
{
    public function __construct(
        private AccountCommandInterface $command,
        private AccountRepositoryInterface $repository,
        private AccountForm $form
    ) {}

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
        $this->command->insertAccount($account);

        return $this->redirect()->toRoute('accounts');
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        if (! $id) {
            return $this->redirect()->toRoute('accounts/add');
        }

        try {
            $account = $this->repository->find($id);
        } catch (InvalidArgumentException $ex) {
            return $this->redirect()->toRoute('accounts');
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
        $account = $this->command->updateAccount($account);

        return $this->redirect()->toRoute(
            'accounts/view',
            ['id' => $account->getAccountId()]
        );
    }
}