<?php

namespace Accounting\Controller;

use Accounting\Model\AccountRepositoryInterface;
use Accounting\Form\AccountForm;
use Accounting\Model\Account;
use Accounting\ValueObject\AccountType;
use Laminas\Mvc\Controller\AbstractActionController;

class AccountController extends AbstractActionController
{
    public function __construct(private AccountRepositoryInterface $accountRepo){}

    public function indexAction()
    {
        return [
            'accounts' => $this->accountRepo->all(),
        ];
    }

    public function addAction()
    {
        $form = new AccountForm();
        $form->get('submit')->setValue('Add');

        $request = $this->getRequest();

        if (! $request->isPost()) {
            return ['form' => $form];
        }

        $form->setData($request->getPost());

        if (! $form->isValid()) {
            return ['form' => $form];
        }

        $data = $form->getData();
        $account = new Account(
            null,
            $data['name'],
            AccountType::from($data['account_type']),
        );
        $this->accountRepo->save($account);

        return $this->redirect()->toRoute('account');
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('accounts', ['action' => 'add']);
        }

        return [
            'account' => $this->accountRepo->find($id),
        ];
    }

    public function deleteAction()
    {

    }
}